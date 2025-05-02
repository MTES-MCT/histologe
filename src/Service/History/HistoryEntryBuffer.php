<?php

namespace App\Service\History;

use App\Entity\Signalement;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class HistoryEntryBuffer
{
    public array $pendingHistoryEntries = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function flushPendingHistoryEntries(): void
    {
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
