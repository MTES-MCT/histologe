<?php

namespace App\Entity;

use App\Entity\Behaviour\TimestampableTrait;
use App\Repository\DesordreCategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DesordreCategorieRepository::class)]
class DesordreCategorie
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\OneToMany(mappedBy: 'desordreCategorie', targetEntity: DesordreCritere::class, orphanRemoval: true)]
    private Collection $desordreCriteres;

    public function __construct()
    {
        $this->desordreCriteres = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return Collection<int, DesordreCritere>
     */
    public function getDesordreCriteres(): Collection
    {
        return $this->desordreCriteres;
    }

    public function addDesordreCritere(DesordreCritere $desordreCritere): static
    {
        if (!$this->desordreCriteres->contains($desordreCritere)) {
            $this->desordreCriteres->add($desordreCritere);
            $desordreCritere->setDesordreCategorie($this);
        }

        return $this;
    }

    public function removeDesordreCritere(DesordreCritere $desordreCritere): static
    {
        if ($this->desordreCriteres->removeElement($desordreCritere)) {
            // set the owning side to null (unless already changed)
            if ($desordreCritere->getDesordreCategorie() === $this) {
                $desordreCritere->setDesordreCategorie(null);
            }
        }

        return $this;
    }
}
