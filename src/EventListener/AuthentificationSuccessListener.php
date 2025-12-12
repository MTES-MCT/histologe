<?php

namespace App\EventListener;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\User;
use App\Manager\HistoryEntryManager;
use App\Manager\UserManager;
use App\Repository\SignalementRepository;
use App\Security\User\SignalementBailleur;
use App\Security\User\SignalementUser;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class AuthentificationSuccessListener
{
    private const string CHECK_2FA_PATH = '/2fa_check';

    public function __construct(
        private readonly HistoryEntryManager $historyEntryManager,
        private readonly UserManager $userManager,
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
        if ($event->getAuthenticationToken()->getUser() instanceof User || $event->getAuthenticationToken()->getUser() instanceof SignalementBailleur) {
            $user = $event->getAuthenticationToken()->getUser();
        } elseif ($event->getAuthenticationToken()->getUser() instanceof SignalementUser) {
            $user = $event->getAuthenticationToken()->getUser()->getUser();
        }
        if (!isset($user)) {
            return;
        }
        $this->handleSuccessfulLogin(
            $user,
            HistoryEntryEvent::LOGIN
        );
    }

    public function onSchebTwoFactorAuthenticationSuccess(TwoFactorAuthenticationEvent $event): void
    {
        /** @var User $user */
        $user = $event->getToken()->getUser();
        $this->handleSuccessfulLogin(
            $user,
            HistoryEntryEvent::LOGIN_2FA
        );
    }

    private function handleSuccessfulLogin(?UserInterface $user, HistoryEntryEvent $eventType): void
    {
        if (!$user) {
            return;
        }

        if ($user instanceof User) {
            $user->setLastLoginAt(new \DateTimeImmutable());
            $this->userManager->save($user, true);
        }

        $this->createAuthentificationHistory($eventType, $user);
    }

    private function createAuthentificationHistory(HistoryEntryEvent $historyEntryEvent, UserInterface $user): void
    {
        if (!$this->historyTrackingEnable) {
            return;
        }
        try {
            $signalement = null;
            if ($user instanceof SignalementUser) {
                $signalement = $this->signalementRepository->findOneByCodeForPublic($user->getCodeSuivi());
            } elseif ($user instanceof SignalementBailleur) {
                $signalement = $this->signalementRepository->findOneBy(['uuid' => $user->getUserIdentifier()]);
            }
            /** @var EntityHistoryInterface $entityHistory */
            $entityHistory = $user instanceof SignalementUser || $user instanceof SignalementBailleur ? $signalement : $user;
            $historyEntry = $this->historyEntryManager->create(
                historyEntryEvent: $historyEntryEvent,
                entityHistory: $entityHistory,
            );

            $source = $this->historyEntryManager->getSource();
            $historyEntry->setSource($source);
            $this->historyEntryManager->save($historyEntry);

            return;
        } catch (\Throwable $exception) {
            if ($user instanceof SignalementUser || $user instanceof SignalementBailleur) {
                if ($user instanceof SignalementUser) {
                    $signalement = $this->signalementRepository->findOneByCodeForPublic($user->getCodeSuivi());
                } else {
                    $signalement = $this->signalementRepository->findOneBy(['uuid' => $user->getUserIdentifier()]);
                }
                $this->logger->error(\sprintf(
                    'Failed to create login history entry (%s) on signalement : %d',
                    $exception->getMessage(),
                    $signalement->getId()
                ));
            } elseif ($user instanceof User) {
                $this->logger->error(\sprintf(
                    'Failed to create login history entry (%s) on user : %d',
                    $exception->getMessage(),
                    $user->getId()
                ));
            } else {
                $this->logger->error('Failed unknown user type');
            }
        }
    }
}
