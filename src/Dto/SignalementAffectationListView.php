<?php

namespace App\Dto;

use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;

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
        private ?string $score = null,
        private ?bool $isNotOccupant = null,
        private ?string $nomOccupant = null,
        private ?string $prenomOccupant = null,
        private ?string $adresseOccupant = null,
        private ?string $villeOccupant = null,
        private \DateTimeImmutable|string|null $lastSuiviAt = null,
        private ?string $lastSuiviBy = null,
        private ?array $affectations = null,
        private ?array $qualifications = null,
        private ?array $qualificationsStatuses = null,
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

    public function getScore(): ?string
    {
        return $this->score;
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

    public function getQualificationsLabels(): array
    {
        $listLabels = [];

        if (null !== $this->qualifications) {
            foreach ($this->qualifications as $qualification) {
                if (Qualification::NON_DECENCE_ENERGETIQUE->name !== $qualification) {
                    $listLabels[] = Qualification::tryFrom($qualification)?->label();
                }
            }
        }

        return $listLabels;
    }

    public function getQualificationsStatusesLabels(): array
    {
        $listLabels = [];

        if (null !== $this->qualificationsStatuses) {
            foreach ($this->qualificationsStatuses as $qualificationStatus) {
                if (false === strpos(Qualification::NON_DECENCE_ENERGETIQUE->name, 'NDE')) {
                    $listLabels[] = QualificationStatus::tryFrom($qualificationStatus)?->label();
                }
            }
        }

        return $listLabels;
    }
}
