<?php

namespace App\Dto;

use App\Entity\Affectation;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Service\InjonctionBailleur\InjonctionBailleurService;
use Symfony\Component\Serializer\Attribute\Groups;

#[Groups(['signalements:read'])]
class SignalementAffectationListView
{
    public const string SEPARATOR_CONCAT = '||';
    public const string SEPARATOR_GROUP_CONCAT = ';';
    public const int MAX_LIST_PAGINATION = 30;

    /** @var array<string> */
    private ?array $qualificationsStatusesLabels = null;

    /**
     * @param array<Affectation>   $affectations
     * @param array<Qualification> $qualifications
     * @param array<string>        $qualificationsStatuses
     * @param array<mixed>         $conclusionsProcedure
     */
    public function __construct(
        private readonly ?int $id = null,
        private readonly ?string $uuid = null,
        private readonly ?string $reference = null,
        private readonly ?string $referenceInjonction = null,
        private readonly ?\DateTimeImmutable $createdAt = null,
        private readonly ?SignalementStatus $statut = null,
        private readonly ?string $score = null,
        private readonly ?bool $isNotOccupant = null,
        private readonly ?string $nomOccupant = null,
        private readonly ?string $prenomOccupant = null,
        private readonly ?string $adresseOccupant = null,
        private readonly ?string $codepostalOccupant = null,
        private readonly ?string $villeOccupant = null,
        private readonly \DateTimeImmutable|string|null $lastSuiviAt = null,
        private readonly ?string $lastSuiviBy = null,
        private readonly ?bool $lastSuiviIsPublic = null,
        private readonly ?string $profileDeclarant = null,
        private readonly ?array $affectations = null,
        private readonly ?array $qualifications = null,
        private readonly ?array $qualificationsStatuses = null,
        private readonly ?array $conclusionsProcedure = null,
        private ?string $csrfToken = null,
        private readonly ?bool $canDeleteSignalement = false,
        private readonly ?bool $isLogementSocial = false,
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

    public function getReferenceInjonction(): ?string
    {
        if ($this->referenceInjonction) {
            return InjonctionBailleurService::REFERENCE_PREFIX.$this->referenceInjonction;
        }

        return null;
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

    /** @return array<Affectation> */
    public function getAffectations(): array
    {
        return $this->affectations;
    }

    /** @return array<Qualification> */
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

    /** @return array<string> */
    public function getQualificationsStatuses(): ?array
    {
        return $this->qualificationsStatuses;
    }

    /** @return array<string> */
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

    /** @return array<mixed> */
    public function getConclusionsProcedure(): ?array
    {
        return $this->conclusionsProcedure;
    }

    public function getCanDeleteSignalement(): bool
    {
        return $this->canDeleteSignalement;
    }

    public function getIsLogementSocial(): ?bool
    {
        return $this->isLogementSocial;
    }
}
