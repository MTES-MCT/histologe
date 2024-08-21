<?php

namespace App\Manager;

use App\Dto\Command\CommandContext;
use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\HistoryEntry;
use App\Factory\HistoryEntryFactory;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class HistoryEntryManager extends AbstractManager
{
    public function __construct(
        private readonly HistoryEntryFactory $historyEntryFactory,
        private readonly NormalizerInterface $normalizer,
        private readonly RequestStack $requestStack,
        private readonly CommandContext $commandContext,
        ManagerRegistry $managerRegistry,
        string $entityName = self::class
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function create(
        HistoryEntryEvent $historyEntryEvent,
        EntityHistoryInterface $entityHistory,
        bool $flush = true,
    ): HistoryEntry {
        $historyEntry = $this->historyEntryFactory->createInstanceFrom(
            historyEntryEvent: $historyEntryEvent,
            entityHistory: $entityHistory,
        );

        $this->save($historyEntry, $flush);

        return $historyEntry;
    }

    /**
     * @throws ExceptionInterface
     */
    public function getChangesAndSource(
        HistoryEntryEvent $historyEntryEvent,
        EntityHistoryInterface $entityHistory,
    ): array {
        $changesFromDelete = [];
        if (HistoryEntryEvent::DELETE === $historyEntryEvent) {
            $changesFromDelete = $this->normalizer->normalize(
                $entityHistory,
                null,
                ['groups' => ['history_entry:read']]
            );
        }

        $source = $this->requestStack->getCurrentRequest()?->getPathInfo() ?? $this->commandContext->getCommandName();

        return [
            $changesFromDelete,
            $source,
        ];
    }
}
