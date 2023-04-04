<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\NotificationService;
use App\Service\Token\TokenGeneratorInterface;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserUpdatedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private UrlGeneratorInterface $urlGenerator,
        private NotificationService $notificationService,
        private TokenGeneratorInterface $tokenGenerator,
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
            if ($entity instanceof User && \array_key_exists('email', $changes)) {
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
        if (\in_array('ROLE_USER_PARTNER', $user->getRoles())
        || \in_array('ROLE_ADMIN_PARTNER', $user->getRoles())
        || \in_array('ROLE_ADMIN_TERRITORY', $user->getRoles())
        || \in_array('ROLE_ADMIN', $user->getRoles())
        ) {
            $this->notificationService->send(
                NotificationService::TYPE_ACCOUNT_ACTIVATION,
                $user->getEmail(),
                ['link' => $this->generateLink($user)],
                $user->getTerritory()
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
