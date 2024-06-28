<?php

namespace App\Factory;

use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\HistoryEntry;
use App\Entity\User;

class HistoryEntryFactory
{
    public function createInstanceFrom(
        HistoryEntryEvent $historyEntryEvent,
        int $entityId,
        string $entityName,
        ?User $user,
    ): HistoryEntry {
        return (new HistoryEntry())
            ->setEvent($historyEntryEvent)
            ->setEntityId($entityId)
            ->setEntityName($entityName)
            ->setUser($user);
    }
}
