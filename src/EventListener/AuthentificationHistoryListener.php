<?php

namespace App\EventListener;

use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\User;
use App\Manager\HistoryEntryManager;
use App\Repository\SignalementRepository;
use App\Security\User\SignalementUser;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class AuthentificationHistoryListener
{
    private const string CHECK_2FA_PATH = '/2fa_check';

    public function __construct(
        private readonly HistoryEntryManager $historyEntryManager,
        private readonly LoggerInterface $logger,
        private readonly SignalementRepository $signalementRepository,
        #[Autowire(env: 'HISTORY_TRACKING_ENABLE')]
        private readonly string $historyTrackingEnable,
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

    private function createAuthentificationHistory(HistoryEntryEvent $historyEntryEvent, SignalementUser|User $user): void
    {
        if (!$this->historyTrackingEnable) {
            return;
        }
        try {
            if ($user instanceof SignalementUser) {
                $signalement = $this->signalementRepository->findOneByCodeForPublic($user->getCodeSuivi());
            }
            $historyEntry = $this->historyEntryManager->create(
                historyEntryEvent: $historyEntryEvent,
                entityHistory: $user instanceof SignalementUser ? $signalement : $user,
            );

            $source = $this->historyEntryManager->getSource();
            $historyEntry->setSource($source);
            $this->historyEntryManager->save($historyEntry);

            return;
        } catch (\Throwable $exception) {
            if ($user instanceof SignalementUser) {
                $signalement = $this->signalementRepository->findOneByCodeForPublic($user->getCodeSuivi());
                $this->logger->error(\sprintf(
                    'Failed to create login history entry (%s) on signalement : %d',
                    $exception->getMessage(),
                    $signalement->getId()
                ));
            } else {
                $this->logger->error(\sprintf(
                    'Failed to create login history entry (%s) on user : %d',
                    $exception->getMessage(),
                    $user->getId()
                ));
            }
        }
    }
}
