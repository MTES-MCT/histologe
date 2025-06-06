<?php

namespace App\Entity;

use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Repository\SignalementQualificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SignalementQualificationRepository::class)]
class SignalementQualification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'signalementQualifications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Signalement $signalement = null;

    #[ORM\Column(type: 'string', enumType: Qualification::class)]
    private ?Qualification $qualification = null;

    /** @var array<Criticite> $criticites */
    #[ORM\Column(nullable: true)]
    private ?array $criticites = [];

    /** @var array<mixed> $desordrePrecisionIds */
    #[ORM\Column(nullable: true)]
    private ?array $desordrePrecisionIds = [];

    /** @var array<mixed> $details */
    #[ORM\Column(nullable: true)]
    private array $details = [];

    #[ORM\Column(type: 'string', nullable: true, enumType: QualificationStatus::class)]
    private ?QualificationStatus $status = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isPostVisite = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSignalement(): ?Signalement
    {
        return $this->signalement;
    }

    public function setSignalement(?Signalement $signalement): self
    {
        $this->signalement = $signalement;

        return $this;
    }

    public function getQualification(): ?Qualification
    {
        return $this->qualification;
    }

    public function setQualification(Qualification $qualification): self
    {
        $this->qualification = $qualification;

        return $this;
    }

    public function isNDE(): bool
    {
        return Qualification::NON_DECENCE_ENERGETIQUE == $this->qualification
            && (QualificationStatus::NDE_AVEREE == $this->status || QualificationStatus::NDE_CHECK == $this->status);
    }

    /** @return array<Criticite> */
    public function getCriticites(): array
    {
        return $this->criticites;
    }

    /** @param array<Criticite> $criticites */
    public function setCriticites(?array $criticites): self
    {
        $this->criticites = $criticites;

        return $this;
    }

    /** @return array<int> */
    public function getDesordrePrecisionIds(): ?array
    {
        return $this->desordrePrecisionIds;
    }

    /** @param array<int> $desordrePrecisionIds */
    public function setDesordrePrecisionIds(?array $desordrePrecisionIds): self
    {
        $this->desordrePrecisionIds = $desordrePrecisionIds;

        return $this;
    }

    public function hasDesordres(): bool
    {
        if (null !== $this->getDesordrePrecisionIds() && \count($this->getDesordrePrecisionIds()) > 0) {
            return true;
        }
        if (null !== $this->getCriticites() && \count($this->getCriticites()) > 0) {
            return true;
        }

        return false;
    }

    /** @return array<mixed> */
    public function getDetails(): array
    {
        return $this->details;
    }

    /** @param array<mixed> $details */
    public function setDetails(?array $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getStatus(): ?QualificationStatus
    {
        return $this->status;
    }

    public function setStatus(QualificationStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function isPostVisite(): ?bool
    {
        return $this->isPostVisite;
    }

    public function setIsPostVisite(?bool $isPostVisite): self
    {
        $this->isPostVisite = $isPostVisite;

        return $this;
    }
}
