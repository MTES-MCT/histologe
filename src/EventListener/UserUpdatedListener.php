<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Token\TokenGeneratorInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsDoctrineListener(event: Events::onFlush)]
class UserUpdatedListener
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private TokenGeneratorInterface $tokenGenerator,
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

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $changes = $unitOfWork->getEntityChangeSet($entity);

            if ($entity instanceof User && $this->shouldChangePassword($changes)) {
                $entity->setPassword($this->tokenGenerator->generateToken())
                ->setToken($this->tokenGenerator->generateToken())
                ->setTokenExpiredAt(
                    (new \DateTimeImmutable())->modify($this->parameterBag->get('token_lifetime'))
                );

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

    private function shouldChangePassword(array $changes): bool
    {
        if (\array_key_exists('email', $changes) // if email has changed
            || (\array_key_exists('roles', $changes) && \in_array('ROLE_USAGER', $changes['roles'][0]))// if usager becomes user
        ) {
            return true;
        }

        return false;
    }
}
