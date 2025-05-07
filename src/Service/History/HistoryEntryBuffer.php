<?php

namespace App\Service\History;

use App\Entity\HistoryEntry;
use App\Entity\Signalement;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class HistoryEntryBuffer
{
    private array $pendingHistoryEntries = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function exist(string $key): bool
    {
        return isset($this->pendingHistoryEntries[$key]);
    }

    public function add(string $key, HistoryEntry $entry): void
    {
        $this->pendingHistoryEntries[$key] = $entry;
    }

    public function update(string $key, array $changes): void
    {
        $oldChanges = $this->pendingHistoryEntries[$key]->getChanges();
        $this->pendingHistoryEntries[$key]->setChanges(array_merge($oldChanges, $changes));
    }

    public function flushPendingHistoryEntries(): void
    {
        if (empty($this->pendingHistoryEntries)) {
            return;
        }
        // for prevent flushing invalid entities
        $this->entityManager->clear();
        foreach ($this->pendingHistoryEntries as $entry) {
            if (!$entry->getEntityId() && $entry->getEntity()?->getId()) {
                $entry->setEntityId($entry->getEntity()->getId());
            }
            if ($entry->getSignalement() && !$this->entityManager->contains($entry->getSignalement())) {
                $signalement = $this->entityManager->getRepository(Signalement::class)->find($entry->getSignalement()->getId());
                $entry->setSignalement($signalement);
            }
            if ($entry->getUser() && !$this->entityManager->contains($entry->getUser())) {
                $user = $this->entityManager->getRepository(User::class)->find($entry->getUser()->getId());
                $entry->setUser($user);
            }

            $this->entityManager->persist($entry);
        }
        $this->entityManager->flush();
        $this->pendingHistoryEntries = [];
    }
}
