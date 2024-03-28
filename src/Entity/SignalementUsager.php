<?php

namespace App\Entity;

use App\Repository\SignalementUsagerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SignalementUsagerRepository::class)]
class SignalementUsager
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Signalement $signalement = null;

    #[ORM\ManyToOne(inversedBy: 'signalementUsagerDeclarants')]
    private ?User $declarant = null;

    #[ORM\ManyToOne(inversedBy: 'signalementUsagerOccupants')]
    private ?User $occupant = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDeclarant(): ?User
    {
        return $this->declarant;
    }

    public function setDeclarant(?User $declarant): self
    {
        $this->declarant = $declarant;

        return $this;
    }

    public function getOccupant(): ?User
    {
        return $this->occupant;
    }

    public function setOccupant(?User $occupant): self
    {
        $this->occupant = $occupant;

        return $this;
    }
}
