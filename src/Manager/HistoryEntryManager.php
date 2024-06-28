<?php

namespace App\Manager;

use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\HistoryEntry;
use App\Entity\User;
use App\Factory\HistoryEntryFactory;
use Doctrine\Persistence\ManagerRegistry;

class HistoryEntryManager extends AbstractManager
{
    public function __construct(
        private HistoryEntryFactory $historyEntryFactory,
        ManagerRegistry $managerRegistry,
        string $entityName = self::class
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function create(
        HistoryEntryEvent $historyEntryEvent,
        int $entityId,
        string $entityName,
        ?User $user,
    ): HistoryEntry {
        $historyEntry = $this->historyEntryFactory->createInstanceFrom(
            historyEntryEvent: $historyEntryEvent,
            entityId: $entityId,
            entityName: $entityName,
            user: $user
        );

        $this->save($historyEntry);

        return $historyEntry;
    }
}
