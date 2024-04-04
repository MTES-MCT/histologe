<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: User::class)]
class UserUpdatedListener
{
    public function __construct(
        private NotificationMailerRegistry $notificationMailerRegistry
    ) {
    }

    public function postUpdate(User $user, PostUpdateEventArgs $event): void
    {
        $unitOfWork = $event->getObjectManager()->getUnitOfWork();
        $changes = $unitOfWork->getEntityChangeSet($user);

        if ($this->shouldChangePassword($changes)) {
            $user->setPassword('');
            $this->sendNotification($user);
        }
    }

    private function sendNotification(User $user): void
    {
        if (!\in_array('ROLE_USAGER', $user->getRoles())
            && User::STATUS_ARCHIVE !== $user->getStatut()
        ) {
            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_ACCOUNT_ACTIVATION_FROM_BO,
                    to: $user->getEmail(),
                    territory: $user->getTerritory(),
                    user: $user,
                )
            );
        }
    }

    private function shouldChangePassword(array $changes): bool
    {
        if (\array_key_exists('email', $changes) // if email has changed
            || (
                \array_key_exists('roles', $changes)
                && \in_array('ROLE_USAGER', $changes['roles'][0])
            ) // if usager becomes user
        ) {
            return true;
        }

        return false;
    }
}
