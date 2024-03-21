<?php

namespace App\Entity;

use App\Repository\BailleurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: BailleurRepository::class)]
#[ORM\UniqueConstraint(columns: ['name'])]
class Bailleur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Ignore]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'bailleur', targetEntity: Signalement::class)]
    #[Ignore]
    private Collection $signalements;

    #[ORM\OneToMany(mappedBy: 'bailleur', targetEntity: BailleurTerritory::class, cascade: ['persist'])]
    #[Ignore]
    private Collection $bailleurTerritories;

    public function __construct()
    {
        $this->signalements = new ArrayCollection();
        $this->bailleurTerritories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, BailleurTerritory>
     */
    public function getBailleurTerritories(): Collection
    {
        return $this->bailleurTerritories;
    }

    public function addBailleurTerritory(BailleurTerritory $bailleurTerritory): self
    {
        if (!$this->bailleurTerritories->contains($bailleurTerritory)) {
            $this->bailleurTerritories->add($bailleurTerritory);
            $bailleurTerritory->setBailleur($this);
        }

        return $this;
    }

    public function removeBailleurTerritory(BailleurTerritory $bailleurTerritory): self
    {
        if ($this->bailleurTerritories->removeElement($bailleurTerritory)) {
            // set the owning side to null (unless already changed)
            if ($bailleurTerritory->getBailleur() === $this) {
                $bailleurTerritory->setBailleur(null);
            }
        }

        return $this;
    }

    public function addTerritory(Territory $territory): self
    {
        $bailleurTerritory = (new BailleurTerritory())->setTerritory($territory);
        $this->addBailleurTerritory($bailleurTerritory);

        return $this;
    }
}
