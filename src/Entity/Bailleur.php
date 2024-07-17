<?php

namespace App\Entity;

use App\Repository\BailleurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: BailleurRepository::class)]
class Bailleur
{
    public const BAILLEUR_RADIE = '[Radié(e)]';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['widget-settings:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['widget-settings:read'])]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'bailleur', targetEntity: Signalement::class)]
    #[Ignore]
    private Collection $signalements;

    #[ORM\OneToMany(mappedBy: 'bailleur', targetEntity: BailleurTerritory::class, cascade: ['persist'])]
    #[Ignore]
    private Collection $bailleurTerritories;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $raisonSociale = null;

    #[ORM\Column(length: 20, unique: true, nullable: true)]
    private ?string $siret = null;

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

    public function getRaisonSociale(): ?string
    {
        return $this->raisonSociale;
    }

    public function setRaisonSociale(?string $raisonSociale): static
    {
        $this->raisonSociale = $raisonSociale;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): static
    {
        $this->siret = $siret;

        return $this;
    }
}
