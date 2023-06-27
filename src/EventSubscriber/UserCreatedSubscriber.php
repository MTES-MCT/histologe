<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

class UserCreatedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private NotificationMailerRegistry $notificationMailerRegistry
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof User && $entity->isActivateAccountNotificationEnabled()) {
                $this->sendNotification($entity);
            }
        }
    }

    private function sendNotification(User $user): void
    {
        if (!\in_array('ROLE_USAGER', $user->getRoles())) {
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
}
