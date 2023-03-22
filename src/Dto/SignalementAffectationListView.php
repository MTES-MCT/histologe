<?php

namespace App\Dto;

use App\Entity\Enum\Qualification;

class SignalementAffectationListView
{
    public const SEPARATOR_CONCAT = '||';
    public const SEPARATOR_GROUP_CONCAT = ';';
    public const MAX_LIST_PAGINATION = 30;

    public function __construct(
        private ?int $id = null,
        private ?string $uuid = null,
        private ?string $reference = null,
        private ?\DateTimeImmutable $createdAt = null,
        private ?int $statut = null,
        private ?string $scoreCreation = null,
        private ?string $newScoreCreation = null,
        private ?bool $isNotOccupant = null,
        private ?string $nomOccupant = null,
        private ?string $prenomOccupant = null,
        private ?string $adresseOccupant = null,
        private ?string $villeOccupant = null,
        private \DateTimeImmutable|string|null $lastSuiviAt = null,
        private ?string $lastSuiviBy = null,
        private ?array $affectations = null,
        private ?array $qualifications = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getStatut(): ?int
    {
        return $this->statut;
    }

    public function getScoreCreation(): ?string
    {
        return $this->scoreCreation;
    }

    public function getNewScoreCreation(): ?string
    {
        return $this->newScoreCreation;
    }

    public function getIsNotOccupant(): ?bool
    {
        return $this->isNotOccupant;
    }

    public function getNomOccupant(): ?string
    {
        return $this->nomOccupant;
    }

    public function getPrenomOccupant(): ?string
    {
        return $this->prenomOccupant;
    }

    public function getAdresseOccupant(): ?string
    {
        return $this->adresseOccupant;
    }

    public function getVilleOccupant(): ?string
    {
        return $this->villeOccupant;
    }

    public function getLastSuiviAt(): \DateTimeImmutable|string|null
    {
        return $this->lastSuiviAt;
    }

    public function getLastSuiviBy(): ?string
    {
        return $this->lastSuiviBy;
    }

    public function getAffectations(): array
    {
        return $this->affectations;
    }

    public function getQualifications(): array
    {
        return $this->qualifications;
    }

    public function hasNDE(): bool
    {
        if (null !== $this->qualifications) {
            foreach ($this->qualifications as $qualification) {
                if (Qualification::NON_DECENCE_ENERGETIQUE->name === $qualification) {
                    return true;
                }
            }
        }

        return false;
    }
}
