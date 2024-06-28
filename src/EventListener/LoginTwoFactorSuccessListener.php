<?php

namespace App\EventListener;

use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\User;
use App\Manager\HistoryEntryManager;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class LoginTwoFactorSuccessListener
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
        private HistoryEntryManager $historyEntryManager
    ) {
    }

    public function onSchebTwoFactorAuthenticationSuccess(TwoFactorAuthenticationEvent $event)
    {
        /** @var User $user */
        $user = $event->getToken()->getUser();

        $this->historyEntryManager->create(
            historyEntryEvent: HistoryEntryEvent::LOGIN_2FA,
            entityId: $user->getId(),
            entityName: User::class,
            user: $user
        );
    }
}
