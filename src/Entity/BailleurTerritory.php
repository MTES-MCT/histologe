<?php

namespace App\Entity;

use App\Repository\BailleurTerritoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BailleurTerritoryRepository::class)]
#[ORM\UniqueConstraint(columns: ['bailleur_id', 'territory_id'])]
class BailleurTerritory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bailleurTerritories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Bailleur $bailleur = null;

    #[ORM\ManyToOne(inversedBy: 'bailleurTerritories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Territory $territory = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBailleur(): ?Bailleur
    {
        return $this->bailleur;
    }

    public function setBailleur(?Bailleur $bailleur): static
    {
        $this->bailleur = $bailleur;

        return $this;
    }

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): static
    {
        $this->territory = $territory;

        return $this;
    }

    public function __toString(): string
    {
        return $this->bailleur->getName().'-'.$this->getTerritory()->getName();
    }
}
