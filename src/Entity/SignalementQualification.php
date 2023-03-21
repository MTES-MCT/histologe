<?php

namespace App\Entity;

use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Repository\SignalementQualificationRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
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

    #[ORM\Column(nullable: true)]
    private array $criticites = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $dernierBailAt = null;

    #[ORM\Column(nullable: true)]
    private array $details = [];

    #[ORM\Column(type: 'string', enumType: QualificationStatus::class, nullable: true)]
    private ?QualificationStatus $status = null;

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

    public function getCriticites(): array
    {
        return $this->criticites;
    }

    public function setCriticites(?array $criticites): self
    {
        $this->criticites = $criticites;

        return $this;
    }

    public function getDernierBailAt(): ?DateTimeImmutable
    {
        return $this->dernierBailAt;
    }

    public function setDernierBailAt(?DateTimeImmutable $dernierBailAt): self
    {
        $this->dernierBailAt = $dernierBailAt;

        return $this;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

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
}
