<?php

namespace App\Manager;

use App\Dto\Command\CommandContext;
use App\Entity\Affectation;
use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\MotifRefus;
use App\Entity\HistoryEntry;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Factory\HistoryEntryFactory;
use App\Repository\AffectationRepository;
use App\Repository\HistoryEntryRepository;
use App\Repository\PartnerRepository;
use App\Service\TimezoneProvider;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class HistoryEntryManager extends AbstractManager
{
    public const FORMAT_DATE_TIME = 'Y-m-d H:i:s';

    public function __construct(
        private readonly HistoryEntryFactory $historyEntryFactory,
        private readonly HistoryEntryRepository $historyEntryRepository,
        private readonly AffectationRepository $affectationRepository,
        private readonly PartnerRepository $partnerRepository,
        private readonly RequestStack $requestStack,
        private readonly CommandContext $commandContext,
        ManagerRegistry $managerRegistry,
        string $entityName = HistoryEntry::class
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    /**
     * @throws ExceptionInterface
     */
    public function create(
        HistoryEntryEvent $historyEntryEvent,
        EntityHistoryInterface|Collection $entityHistory,
        array $changes = [],
        bool $flush = true,
    ): ?HistoryEntry {
        $historyEntry = $this->historyEntryFactory->createInstanceFrom(
            historyEntryEvent: $historyEntryEvent,
            entityHistory: $entityHistory,
        );

        $source = $this->getSource();
        $historyEntry
            ->setChanges($changes)
            ->setSource($source);

        $this->save($historyEntry, $flush);

        return $historyEntry;
    }

    /**
     * @throws ExceptionInterface
     */
    public function getSource(
    ): ?string {
        return $this->requestStack->getCurrentRequest()?->getPathInfo() ?? $this->commandContext->getCommandName();
    }

    public function getAffectationHistory(int $signalementId): array
    {
        $affectationHistoryEntries = $this->getHistoryEntries(
            $signalementId,
            Affectation::class
        );

        $signalementHistoryEntries = $this->getHistoryEntries(
            $signalementId,
            Signalement::class,
            HistoryEntryEvent::UPDATE
        );

        $formattedHistory = [];
        $this->formatEntries($formattedHistory, $affectationHistoryEntries, 'affectation');
        $this->formatEntries($formattedHistory, $signalementHistoryEntries, 'signalement');
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

    private function formatEntries(array &$formattedHistory, array $entries, string $type): void
    {
        foreach ($entries as $entry) {
            $userName = $entry->getUser() ? $entry->getUser()->getFullName() : 'Système (automatique)';
            /** @var ?Partner */
            $partner = $entry->getUser()?->getPartner();
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
            $action = 'affectation' === $type ?
                $this->getAffectationActionSummary($entry, $userName, $partnerTarget?->getNom() ?? 'N/A') :
                $this->getSignalementActionSummary($entry, $userName);

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

    private function getSignalementActionSummary(HistoryEntry $entry, string $userName): ?string
    {
        $changes = $entry->getChanges();
        if (array_key_exists('statut', $changes)) {
            $description = $userName;
            switch ($changes['statut']['new']) {
                case Signalement::STATUS_ACTIVE:
                    if (Signalement::STATUS_NEED_VALIDATION === $changes['statut']['old']) {
                        $description .= ' a validé le signalement ';
                    } else {
                        $description .= ' a réouvert le signalement ';
                    }
                    break;
                case Signalement::STATUS_CLOSED:
                    $description .= ' a clôturé le signalement ';
                    break;
                case Signalement::STATUS_ARCHIVED:
                    $description .= ' a archivé le signalement ';
                    break;
                case Signalement::STATUS_REFUSED:
                    $description .= ' a refusé le signalement ';
                    break;
                case Signalement::STATUS_NEED_VALIDATION:
                    $description .= ' a remis le signalement en attente de validation ';
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
                        case Affectation::STATUS_ACCEPTED:
                            if (Affectation::STATUS_WAIT === $changes['statut']['old']) {
                                $description .= ' a accepté son affectation ';
                            } else {
                                $description .= ' a réouvert son affectation ';
                            }
                            break;
                        case Affectation::STATUS_REFUSED:
                            $description .= ' a refusé son affectation ';
                            break;
                        case Affectation::STATUS_CLOSED:
                            $description .= ' a clôturé son affectation ';
                            break;
                        case Affectation::STATUS_WAIT:
                            $description .= ' a remis en attente son affectation ';
                            break;
                        default:
                            $description .= " a modifié son affectation du statut {$changes['statut']['old']} au statut {$changes['statut']['new']}";
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
