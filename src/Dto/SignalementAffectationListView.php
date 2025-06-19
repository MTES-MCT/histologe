<?php

namespace App\Dto;

use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Enum\SignalementStatus;
use Symfony\Component\Serializer\Attribute\Groups;

#[Groups(['signalements:read'])]
class SignalementAffectationListView
{
    public const string SEPARATOR_CONCAT = '||';
    public const string SEPARATOR_GROUP_CONCAT = ';';
    public const int MAX_LIST_PAGINATION = 30;

    private ?array $qualificationsStatusesLabels = null;

    public function __construct(
        private ?int $id = null,
        private ?string $uuid = null,
        private ?string $reference = null,
        private ?\DateTimeImmutable $createdAt = null,
        private ?SignalementStatus $statut = null,
        private ?string $score = null,
        private ?bool $isNotOccupant = null,
        private ?string $nomOccupant = null,
        private ?string $prenomOccupant = null,
        private ?string $adresseOccupant = null,
        private ?string $codepostalOccupant = null,
        private ?string $villeOccupant = null,
        private \DateTimeImmutable|string|null $lastSuiviAt = null,
        private ?string $lastSuiviBy = null,
        private ?bool $lastSuiviIsPublic = null,
        private ?string $profileDeclarant = null,
        private ?array $affectations = null,
        private ?array $qualifications = null,
        private ?array $qualificationsStatuses = null,
        private ?array $conclusionsProcedure = null,
        private ?string $csrfToken = null,
        private ?bool $canDeleteSignalement = false,
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

    public function getStatut(): ?SignalementStatus
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

    public function getCodepostalOccupant(): ?string
    {
        return $this->codepostalOccupant;
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

    public function getLastSuiviIsPublic(): ?bool
    {
        return $this->lastSuiviIsPublic;
    }

    public function getProfileDeclarant(): ?string
    {
        return $this->profileDeclarant;
    }

    public function getAffectations(): array
    {
        return $this->affectations;
    }

    public function getQualifications(): array
    {
        return $this->qualifications;
    }

    #[Groups(['signalements:read'])]
    public function hasNde(): bool
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

    public function getQualificationsStatuses(): ?array
    {
        return $this->qualificationsStatuses;
    }

    public function getQualificationsStatusesLabels(): array
    {
        $this->qualificationsStatusesLabels = [];

        if (null !== $this->qualificationsStatuses) {
            foreach ($this->qualificationsStatuses as $qualificationStatus) {
                $qualificationStatusName = QualificationStatus::tryFrom($qualificationStatus)?->name;
                if ($qualificationStatusName
                        && !str_contains($qualificationStatusName, 'NDE')
                        && str_contains($qualificationStatusName, 'CHECK')
                ) {
                    $this->qualificationsStatusesLabels[] = QualificationStatus::tryFrom($qualificationStatus)?->label();
                }
            }
        }

        return $this->qualificationsStatusesLabels;
    }

    public function getCsrfToken(): ?string
    {
        return $this->csrfToken;
    }

    public function setCsrfToken(string $csrfToken): self
    {
        $this->csrfToken = $csrfToken;

        return $this;
    }

    public function getConclusionsProcedure(): ?array
    {
        return $this->conclusionsProcedure;
    }

    public function getCanDeleteSignalement(): bool
    {
        return $this->canDeleteSignalement;
    }
}
