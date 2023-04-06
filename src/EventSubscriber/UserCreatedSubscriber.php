<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserCreatedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private UrlGeneratorInterface $urlGenerator,
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
            if ($entity instanceof User) {
                $this->sendNotification($entity);
            }
        }
    }

    private function sendNotification(User $user): void
    {
        if (!\in_array('ROLE_USAGER', $user->getRoles())) {
            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    NotificationMailerType::TYPE_ACCOUNT_ACTIVATION,
                    $user->getEmail(),
                    ['link' => $this->generateLink($user)],
                    $user->getTerritory()
                )
            );
        }
    }

    private function generateLink(User $user): string
    {
        return
            $this->parameterBag->get('host_url').
            $this->urlGenerator->generate('activate_account', ['token' => $user->getToken()]);
    }
}
