<?php

namespace App\Entity\Behaviour;

/**
 * État temporaire des changements détectés sur l'entité.
 */
trait EntityChangesTrait
{
    /**
     * Flag technique indiquant qu'une mise à jour Doctrine a été détecté.
     */
    private bool $updateOccurred = false;

    /**
     * Tableau structuré calculés pendant un flush.
     *
     * @var array<string, mixed>
     */
    private array $changes = [];

    public function isUpdateOccurred(): bool
    {
        return $this->updateOccurred;
    }

    public function getChanges(): array
    {
        return $this->changes;
    }

    /**
     * Marque qu'une mise à jour a eu lieu (sans détail).
     */
    public function markUpdateOccurred(): void
    {
        $this->updateOccurred = true;
    }

    /**
     * Enregistre les changements détectés de manière temporaire.
     */
    public function registerChanges(array $changes = []): void
    {
        $this->updateOccurred = true;
        $this->changes = $changes;
    }
}
