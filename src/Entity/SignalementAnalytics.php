<?php

namespace App\Entity;

use App\Repository\SignalementAnalyticsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SignalementAnalyticsRepository::class)]
class SignalementAnalytics
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $lastSuiviAt = null;

    #[ORM\ManyToOne]
    private ?User $lastSuiviUserBy = null;

    #[ORM\OneToOne(cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Signalement $signalement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastSuiviAt(): ?\DateTimeImmutable
    {
        return $this->lastSuiviAt;
    }

    public function setLastSuiviAt(\DateTimeImmutable $lastSuiviAt): self
    {
        $this->lastSuiviAt = $lastSuiviAt;

        return $this;
    }

    public function getLastSuiviUserBy(): ?User
    {
        return $this->lastSuiviUserBy;
    }

    public function setLastSuiviUserBy(?User $lastSuiviUserBy): self
    {
        $this->lastSuiviUserBy = $lastSuiviUserBy;

        return $this;
    }

    public function getSignalement(): ?Signalement
    {
        return $this->signalement;
    }

    public function setSignalement(Signalement $signalement): self
    {
        $this->signalement = $signalement;

        return $this;
    }
}
