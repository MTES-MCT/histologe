<?php

namespace App\EventListener;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\Enum\SuiviCategory;
use App\Entity\User;
use App\Manager\HistoryEntryManager;
use App\Manager\SuiviManager;
use App\Repository\SignalementRepository;
use App\Security\User\SignalementBailleur;
use App\Security\User\SignalementUser;
use App\Service\History\HistoryEntryBuffer;
use App\Service\Security\NewLoginNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class AuthentificationSuccessListener
{
    /**
     * Sur ces chemins, scheb redéclenche l'interactive login après validation du code :
     * la connexion est tracée par onSchebTwoFactorAuthenticationSuccess() (LOGIN_2FA).
     */
    private const array CHECK_2FA_PATHS = ['/2fa_check', '/2fa-suivi-check'];

    public function __construct(
        private readonly HistoryEntryManager $historyEntryManager,
        private readonly LoggerInterface $logger,
        private readonly SignalementRepository $signalementRepository,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(env: 'HISTORY_TRACKING_ENABLE')]
        private readonly string $historyTrackingEnable,
        private readonly HistoryEntryBuffer $historyEntryBuffer,
        private readonly SuiviManager $suiviManager,
        private readonly NewLoginNotifier $newLoginNotifier,
    ) {
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        if (\in_array($event->getRequest()->getPathInfo(), self::CHECK_2FA_PATHS, true)) {
            return;
        }
        $user = $this->resolveLoggedUser($event->getAuthenticationToken()->getUser());
        if (null === $user) {
            return;
        }
        $this->handleSuccessfulLogin(
            $user,
            HistoryEntryEvent::LOGIN,
            $event->getRequest(),
        );
    }

    public function onSchebTwoFactorAuthenticationSuccess(TwoFactorAuthenticationEvent $event): void
    {
        $this->handleSuccessfulLogin(
            $this->resolveLoggedUser($event->getToken()->getUser()),
            HistoryEntryEvent::LOGIN_2FA,
            $event->getRequest(),
        );
    }

    /**
     * Un usager (SignalementUser) est tracé via l'entité User qu'il encapsule.
     */
    private function resolveLoggedUser(?UserInterface $user): User|SignalementBailleur|null
    {
        if ($user instanceof User || $user instanceof SignalementBailleur) {
            return $user;
        }
        if ($user instanceof SignalementUser) {
            return $user->getUser();
        }

        return null;
    }

    private function handleSuccessfulLogin(?UserInterface $user, HistoryEntryEvent $eventType, Request $request): void
    {
        if (!$user) {
            return;
        }

        if ($user instanceof User) {
            $user->setLastLoginAt(new \DateTimeImmutable());
            $this->newLoginNotifier->handleLogin($user, $request);
            $this->entityManager->flush();
        } elseif ($user instanceof SignalementBailleur) {
            $signalement = $this->signalementRepository->findOneByUuidWithSuivis($user->getUserIdentifier());
            if ($signalement && !$signalement->hasSuiviWithCategory(SuiviCategory::INJONCTION_BAILLEUR_LOGIN_BAILLEUR)) {
                $this->suiviManager->createSuivi(
                    signalement: $signalement,
                    description: '',
                    category: SuiviCategory::INJONCTION_BAILLEUR_LOGIN_BAILLEUR,
                );
            }
            $this->entityManager->flush();
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

            $historyKey = $entityHistory::class.'_'.$entityHistory->getId().'_'.$historyEntryEvent->value;
            $this->historyEntryBuffer->add($historyKey, $historyEntry);

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
