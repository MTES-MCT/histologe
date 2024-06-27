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
            ->setCreatedAt(new \DateTimeImmutable())
            ->setEvent($historyEntryEvent->value)
            ->setEntityId($entityId)
            ->setEntityName($entityName)
            ->setUser($user);
    }
}
