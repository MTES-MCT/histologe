<?php

namespace App\Manager;

use App\Dto\Command\CommandContext;
use App\Entity\Affectation;
use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\MotifRefus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\HistoryEntry;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\User;
use App\Entity\UserSignalementSubscription;
use App\EventListener\Behaviour\DoctrineListenerRemoverTrait;
use App\EventListener\EntityHistoryListener;
use App\Factory\HistoryEntryFactory;
use App\Repository\AffectationRepository;
use App\Repository\HistoryEntryRepository;
use App\Repository\PartnerRepository;
use App\Repository\UserRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use App\Service\TimezoneProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class HistoryEntryManager extends AbstractManager
{
    use DoctrineListenerRemoverTrait;

    public const string FORMAT_DATE_TIME = 'Y-m-d H:i:s';

    public function __construct(
        private readonly HistoryEntryFactory $historyEntryFactory,
        private readonly HistoryEntryRepository $historyEntryRepository,
        private readonly AffectationRepository $affectationRepository,
        private readonly UserSignalementSubscriptionRepository $userSignalementSubscriptionRepository,
        private readonly UserRepository $userRepository,
        private readonly PartnerRepository $partnerRepository,
        private readonly RequestStack $requestStack,
        private readonly CommandContext $commandContext,
        ManagerRegistry $managerRegistry,
        string $entityName = HistoryEntry::class,
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    /**
     * @param array<string, mixed> $changes
     *
     * @throws ExceptionInterface
     */
    public function create(
        HistoryEntryEvent $historyEntryEvent,
        EntityHistoryInterface $entityHistory,
        array $changes = [],
    ): ?HistoryEntry {
        $historyEntry = $this->historyEntryFactory->createInstanceFrom(
            historyEntryEvent: $historyEntryEvent,
            entityHistory: $entityHistory,
        );

        $source = $this->getSource();
        $historyEntry
            ->setChanges($changes)
            ->setSource($source);

        return $historyEntry;
    }

    /**
     * @throws ExceptionInterface
     */
    public function getSource(
    ): ?string {
        return $this->requestStack->getCurrentRequest()?->getPathInfo() ?? $this->commandContext->getCommandName();
    }

    /**
     * @return array<string, array<array<string, string>>>
     */
    public function getAffectationHistory(Signalement $signalement): array
    {
        $affectationHistoryEntries = $this->getHistoryEntries(
            $signalement->getId(),
            Affectation::class
        );

        $signalementHistoryEntries = $this->getHistoryEntries(
            $signalement->getId(),
            Signalement::class,
            HistoryEntryEvent::UPDATE
        );

        $userSignalementSubscriptionHistoryEntries = $this->getHistoryEntries(
            $signalement->getId(),
            UserSignalementSubscription::class,
            HistoryEntryEvent::CREATE
        );

        $formattedHistory = [];
        $this->formatEntries($formattedHistory, $affectationHistoryEntries, 'affectation');
        $this->formatEntries($formattedHistory, $signalementHistoryEntries, 'signalement', $signalement);
        $this->formatEntries($formattedHistory, $userSignalementSubscriptionHistoryEntries, 'subscription');
        if (isset($formattedHistory['N/A'])) {
            unset($formattedHistory['N/A']);
        }
        $formattedHistory = array_filter($formattedHistory, function ($entry) {
            return !empty($entry);
        });

        ksort($formattedHistory);
        foreach ($formattedHistory as &$partnerEvents) {
            usort($partnerEvents, fn ($a, $b) => strcasecmp($b['Date'], $a['Date']));
        }

        return $formattedHistory;
    }

    public function removeEntityListeners(): void
    {
        /** @var EntityManagerInterface $objectManager */
        $objectManager = $this->managerRegistry->getManager();
        $eventManager = $objectManager->getEventManager();
        $this->removeListeners(
            $eventManager,
            EntityHistoryListener::class,
            [Events::onFlush]
        );
    }

    /**
     * @return array<HistoryEntry>
     */
    private function getHistoryEntries(int $signalementId, string $entityClass, ?HistoryEntryEvent $event = null): array
    {
        $criteria = ['entityName' => str_replace($this->historyEntryFactory::ENTITY_PROXY_PREFIX, '', $entityClass)];
        if (Signalement::class === $entityClass) {
            $criteria['entityId'] = $signalementId;
            $criteria['event'] = $event;
        } else {
            $criteria['signalement'] = $signalementId;
        }

        return $this->historyEntryRepository->findBy($criteria, ['entityId' => 'ASC', 'createdAt' => 'ASC']);
    }

    /**
     * @param array<string, array<array<string, string>>> $formattedHistory
     * @param array<HistoryEntry>                         $entries
     */
    private function formatEntries(array &$formattedHistory, array $entries, string $type, ?Signalement $signalement = null): void
    {
        /** @var HistoryEntry $entry */
        foreach ($entries as $entry) {
            $userName = $entry->getUser() ? $entry->getUser()->getFullName() : 'Système (automatique)';
            if ('affectation' === $type || 'subscription' === $type) {
                $partner = $entry->getUser()?->getPartnerInTerritoryOrFirstOne($entry->getSignalement()->getTerritory());
            } else {
                $partner = $entry->getUser()?->getPartnerInTerritoryOrFirstOne($signalement->getTerritory());
            }
            $partnerName = $partner ? $partner->getNom() : 'N/A';
            $date = $entry->getCreatedAt()
                ->setTimezone(
                    new \DateTimeZone($partner?->getTerritory() ? $partner->getTerritory()->getTimezone() : TimezoneProvider::TIMEZONE_EUROPE_PARIS)
                )
                ->format(self::FORMAT_DATE_TIME);

            if (!isset($formattedHistory[$partnerName])) {
                $formattedHistory[$partnerName] = [];
            }

            if ('affectation' === $type) {
                $partnerTarget = $this->getPartnerByEntityId($entry->getEntityId()) ?? $this->getPartnerByDeleteHistoryEntry($entry->getEntityId());
            } else {
                $partnerTarget = null;
            }

            $id = $entry->getEntityId();

            switch ($type) {
                case 'affectation':
                    $action = $this->getAffectationActionSummary($entry, $userName, $partnerTarget?->getNom() ?? 'N/A');
                    break;
                case 'subscription':
                    $action = $this->getSubscriptionActionSummary($entry, $userName);
                    break;
                default:
                    $action = $this->getSignalementActionSummary($entry, $userName);
                    break;
            }

            if (null !== $action) {
                $formattedEntry = [
                    'Date' => $date,
                    'Action' => $action,
                    'Id' => 'affectation' === $type ? $id : '-',
                ];
                $formattedHistory[$partnerName][] = $formattedEntry;
                if (null !== $partnerTarget && null !== $partnerTarget->getNom() && $partnerTarget->getNom() !== $partnerName) {
                    $formattedHistory[$partnerTarget->getNom()][] = $formattedEntry;
                }
            }
        }
    }

    private function getSubscriptionActionSummary(HistoryEntry $entry, string $userName): ?string
    {
        $userTarget = $this->getTargetUserSubscribedByEntry($entry);
        $userNameTarget = $userTarget ? $userTarget->getNomComplet() : 'N/A';
        $event = $entry->getEvent();
        switch ($event) {
            case HistoryEntryEvent::CREATE:
                if ($entry->getUser() === $userTarget) {
                    return $userName.' a rejoint le dossier';
                }

                return $userName.' a attribué le dossier à '.$userNameTarget;

            case HistoryEntryEvent::DELETE:
                if ($entry->getUser() === $userTarget) {
                    return $userName.' a quitté le dossier';
                }

                return $userName.' a désattribué le dossier à '.$userNameTarget;

            default:
                return null;
        }
    }

    private function getSignalementActionSummary(HistoryEntry $entry, string $userName): ?string
    {
        $changes = $entry->getChanges();
        if (array_key_exists('statut', $changes)) {
            $description = $userName;
            switch ($changes['statut']['new']) {
                case 2:
                case SignalementStatus::ACTIVE->value:
                    if (SignalementStatus::NEED_VALIDATION->value === $changes['statut']['old']
                        || 1 === $changes['statut']['old']) {
                        $description .= ' a validé le signalement ';
                    } elseif (SignalementStatus::DRAFT->value === $changes['statut']['old']
                        || 0 === $changes['statut']['old']) {
                        $description .= ' a validé le brouillon de signalement ';
                    } else {
                        $description .= ' a réouvert le signalement ';
                    }
                    break;
                case 6:
                case SignalementStatus::CLOSED->value:
                    $description .= ' a fermé le signalement ';
                    break;
                case 7:
                case SignalementStatus::ARCHIVED->value:
                    $description .= ' a archivé le signalement ';
                    break;
                case SignalementStatus::DRAFT_ARCHIVED->value:
                    $description .= ' a archivé le brouillon du signalement ';
                    break;
                case 8:
                case SignalementStatus::REFUSED->value:
                    $description .= ' a refusé le signalement ';
                    break;
                case 1:
                case SignalementStatus::NEED_VALIDATION->value:
                    if (SignalementStatus::DRAFT->value === $changes['statut']['old']
                        || 0 === $changes['statut']['old']) {
                        $description .= ' a créé un signalement ';
                    } else {
                        $description .= ' a remis le signalement en attente de validation ';
                    }
                    break;
                default:
                    $description .= " a modifié le signalement du statut {$changes['statut']['old']} au statut {$changes['statut']['new']}";
                    break;
            }
            if (array_key_exists('motifCloture', $changes) && null !== $changes['motifCloture']['new']) {
                $description .= '(Motif de clôture : '.MotifCloture::tryFrom($changes['motifCloture']['new'])->label().')';
            }
            if (array_key_exists('motifRefus', $changes) && null !== $changes['motifRefus']['new']) {
                $description .= '(Motif de refus : '.MotifRefus::tryFrom($changes['motifRefus']['new'])->label().')';
            }

            return $description;
        }

        return null;
    }

    private function getAffectationActionSummary(HistoryEntry $entry, string $userName, string $partnerName = ''): ?string
    {
        $event = $entry->getEvent();
        $changes = $entry->getChanges();
        switch ($event) {
            case HistoryEntryEvent::CREATE:
                return $userName.' a affecté le signalement au partenaire '.$partnerName;
            case HistoryEntryEvent::UPDATE:
                $description = $userName;
                if (array_key_exists('statut', $changes)) {
                    switch ($changes['statut']['new']) {
                        case AffectationStatus::ACCEPTED->value:
                            if (AffectationStatus::WAIT->value === $changes['statut']['old']) {
                                $description .= ' a accepté l\'affectation pour le partenaire ';
                            } else {
                                $description .= ' a réouvert l\'affectation pour le partenaire ';
                            }
                            break;
                        case AffectationStatus::REFUSED->value:
                            $description .= ' a refusé l\'affectation pour le partenaire ';
                            break;
                        case AffectationStatus::CLOSED->value:
                            $description .= ' a clôturé l\'affectation pour le partenaire ';
                            break;
                        case AffectationStatus::WAIT->value:
                            $description .= ' a remis en attente l\'affectation pour le partenaire ';
                            break;
                        default:
                            $description .= " a modifié l\'affectation pour le partenaire du statut {$changes['statut']['old']} au statut {$changes['statut']['new']}";
                            break;
                    }
                    if (array_key_exists('motifCloture', $changes) && null !== $changes['motifCloture']['new']) {
                        $description .= '(Motif de clôture : '.MotifCloture::tryFrom($changes['motifCloture']['new'])->label().')';
                    }
                    if (array_key_exists('motifRefus', $changes) && null !== $changes['motifRefus']['new']) {
                        $description .= '(Motif de refus : '.MotifRefus::tryFrom($changes['motifRefus']['new'])->label().')';
                    }

                    return $description;
                }

                return null;
            case HistoryEntryEvent::DELETE:
                return $userName." a supprimé l'affectation du partenaire ".$partnerName;
            default:
                return null;
        }
    }

    private function getTargetUserSubscribedByEntry(HistoryEntry $entry): ?User
    {
        // sur du DELETE on va chercher l'utilisateur dans changes
        if (HistoryEntryEvent::DELETE === $entry->getEvent()) {
            return $this->getTargetUserByEntryInChanges($entry);
        }
        // sur du CREATE on va chercher l'utilisateur dans l'entité
        $userSignalementSubscription = $this->userSignalementSubscriptionRepository->find($entry->getEntityId());
        if ($userSignalementSubscription) {
            return $userSignalementSubscription->getUser();
        }
        // Si l'entité CREATE n'existe pas on recherche via le delete correspondant
        $deleteUserSignalementSubscriptionHistoryEntry = $this->historyEntryRepository->findOneBy(
            [
                'entityId' => $entry->getEntityId(),
                'event' => HistoryEntryEvent::DELETE,
                'entityName' => str_replace($this->historyEntryFactory::ENTITY_PROXY_PREFIX, '', UserSignalementSubscription::class),
            ]
        );

        if ($deleteUserSignalementSubscriptionHistoryEntry) {
            return $this->getTargetUserByEntryInChanges($deleteUserSignalementSubscriptionHistoryEntry);
        }

        return null;
    }

    private function getTargetUserByEntryInChanges(HistoryEntry $entry): ?User
    {
        return $this->userRepository->find($entry->getChanges()['user']);
    }

    private function getPartnerByEntityId(int $affectationId): ?Partner
    {
        $affectation = $this->affectationRepository->find($affectationId);

        return $affectation ? $affectation->getPartner() : null;
    }

    private function getPartnerByDeleteHistoryEntry(int $affectationId): ?Partner
    {
        $deleteAffectationHistoryEntry = $this->historyEntryRepository->findOneBy(
            [
                'entityId' => $affectationId,
                'event' => HistoryEntryEvent::DELETE,
                'entityName' => str_replace($this->historyEntryFactory::ENTITY_PROXY_PREFIX, '', Affectation::class),
            ],
            ['entityId' => 'ASC', 'createdAt' => 'ASC']
        );

        if ($deleteAffectationHistoryEntry) {
            $partnerId = $deleteAffectationHistoryEntry->getChanges()['partner'];

            return $this->partnerRepository->find($partnerId);
        }

        return null;
    }
}
