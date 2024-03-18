<?php

namespace App\Entity;

use App\Repository\BailleurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: BailleurRepository::class)]
class Bailleur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Ignore]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    #[Ignore]
    private ?bool $isSocial = null;

    #[ORM\Column]
    #[Ignore]
    private ?bool $active = true;

    #[ORM\ManyToMany(targetEntity: Territory::class, inversedBy: 'bailleurs')]
    #[Ignore]
    private Collection $territories;

    #[ORM\OneToMany(mappedBy: 'bailleur', targetEntity: Signalement::class)]
    #[Ignore]
    private Collection $signalements;

    public function __construct()
    {
        $this->territories = new ArrayCollection();
        $this->signalements = new ArrayCollection();
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

    public function isIsSocial(): ?bool
    {
        return $this->isSocial;
    }

    public function setIsSocial(bool $isSocial): self
    {
        $this->isSocial = $isSocial;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return Collection<int, Territory>
     */
    public function getTerritories(): Collection
    {
        return $this->territories;
    }

    public function addTerritory(Territory $territory): self
    {
        if (!$this->territories->contains($territory)) {
            $this->territories->add($territory);
        }

        return $this;
    }

    public function removeTerritory(Territory $territory): self
    {
        $this->territories->removeElement($territory);

        return $this;
    }

    /**
     * @return Collection<int, Signalement>
     */
    public function getSignalements(): Collection
    {
        return $this->signalements;
    }

    public function addSignalement(Signalement $signalement): self
    {
        if (!$this->signalements->contains($signalement)) {
            $this->signalements->add($signalement);
            $signalement->setBailleur($this);
        }

        return $this;
    }

    public function removeSignalement(Signalement $signalement): self
    {
        if ($this->signalements->removeElement($signalement)) {
            // set the owning side to null (unless already changed)
            if ($signalement->getBailleur() === $this) {
                $signalement->setBailleur(null);
            }
        }

        return $this;
    }
}
