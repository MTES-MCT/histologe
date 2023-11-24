<?php

namespace App\Entity;

use App\Entity\Behaviour\TimestampableTrait;
use App\Repository\DesordreCategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DesordreCategorieRepository::class)]
#[ORM\HasLifecycleCallbacks()]
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

    #[ORM\ManyToMany(targetEntity: Signalement::class, inversedBy: 'desordreCategories')]
    private Collection $signalement;

    public function __construct()
    {
        $this->desordreCriteres = new ArrayCollection();
        $this->signalement = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
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

    public function addDesordreCritere(DesordreCritere $desordreCritere): self
    {
        if (!$this->desordreCriteres->contains($desordreCritere)) {
            $this->desordreCriteres->add($desordreCritere);
            $desordreCritere->setDesordreCategorie($this);
        }

        return $this;
    }

    public function removeDesordreCritere(DesordreCritere $desordreCritere): self
    {
        if ($this->desordreCriteres->removeElement($desordreCritere)) {
            // set the owning side to null (unless already changed)
            if ($desordreCritere->getDesordreCategorie() === $this) {
                $desordreCritere->setDesordreCategorie(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Signalement>
     */
    public function getSignalement(): Collection
    {
        return $this->signalement;
    }

    public function addSignalement(Signalement $signalement): self
    {
        if (!$this->signalement->contains($signalement)) {
            $this->signalement->add($signalement);
        }

        return $this;
    }

    public function removeSignalement(Signalement $signalement): self
    {
        $this->signalement->removeElement($signalement);

        return $this;
    }
}
