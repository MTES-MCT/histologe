<?php

namespace App\EventListener;

use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\User;
use App\Manager\HistoryEntryManager;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class AuthentificationHistoryListener
{
    private const CHECK_2FA_PATH = '/2fa_check';

    public function __construct(
        private readonly HistoryEntryManager $historyEntryManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        if (self::CHECK_2FA_PATH === $event->getRequest()->getPathInfo()) {
            return;
        }
        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();
        $this->createAuthentificationHistory(HistoryEntryEvent::LOGIN, $user);
    }

    public function onSchebTwoFactorAuthenticationSuccess(TwoFactorAuthenticationEvent $event): void
    {
        /** @var User $user */
        $user = $event->getToken()->getUser();
        $this->createAuthentificationHistory(HistoryEntryEvent::LOGIN_2FA, $user);
    }

    private function createAuthentificationHistory(HistoryEntryEvent $historyEntryEvent, User $user): void
    {
        try {
            $this->historyEntryManager->create(
                historyEntryEvent: $historyEntryEvent,
                entityId: $user->getId(),
                entityName: User::class,
                user: $user
            );
        } catch (\Throwable $exception) {
            $this->logger->error(sprintf(
                'Failed to create login history entry (%s) on user : %d',
                $exception->getMessage(),
                $user->getId()
            ));
        }
    }
}
