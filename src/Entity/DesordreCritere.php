<?php

namespace App\Entity;

use App\Entity\Behaviour\TimestampableTrait;
use App\Repository\DesordreCritereRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DesordreCritereRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class DesordreCritere
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $slugCategorie = null;

    #[ORM\Column(length: 255)]
    private ?string $labelCategorie = null;

    #[ORM\Column(length: 255)]
    private ?string $zoneCategorie = null;

    #[ORM\Column(length: 255)]
    private ?string $slugCritere = null;

    #[ORM\Column(length: 255)]
    private ?string $labelCritere = null;

    #[ORM\ManyToOne(inversedBy: 'desordreCriteres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DesordreCategorie $desordreCategorie = null;

    #[ORM\OneToMany(mappedBy: 'desordreCritere', targetEntity: DesordrePrecision::class, orphanRemoval: true)]
    private Collection $desordrePrecisions;

    #[ORM\ManyToMany(targetEntity: Signalement::class, inversedBy: 'desordreCriteres')]
    private Collection $signalement;

    public function __construct()
    {
        $this->desordrePrecisions = new ArrayCollection();
        $this->signalement = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlugCategorie(): ?string
    {
        return $this->slugCategorie;
    }

    public function setSlugCategorie(string $slugCategorie): static
    {
        $this->slugCategorie = $slugCategorie;

        return $this;
    }

    public function getLabelCategorie(): ?string
    {
        return $this->labelCategorie;
    }

    public function setLabelCategorie(string $labelCategorie): static
    {
        $this->labelCategorie = $labelCategorie;

        return $this;
    }

    public function getZoneCategorie(): ?string
    {
        return $this->zoneCategorie;
    }

    public function setZoneCategorie(string $zoneCategorie): static
    {
        $this->zoneCategorie = $zoneCategorie;

        return $this;
    }

    public function getSlugCritere(): ?string
    {
        return $this->slugCritere;
    }

    public function setSlugCritere(string $slugCritere): static
    {
        $this->slugCritere = $slugCritere;

        return $this;
    }

    public function getLabelCritere(): ?string
    {
        return $this->labelCritere;
    }

    public function setLabelCritere(string $labelCritere): static
    {
        $this->labelCritere = $labelCritere;

        return $this;
    }

    public function getDesordreCategorie(): ?DesordreCategorie
    {
        return $this->desordreCategorie;
    }

    public function setDesordreCategorie(?DesordreCategorie $desordreCategorie): static
    {
        $this->desordreCategorie = $desordreCategorie;

        return $this;
    }

    /**
     * @return Collection<int, DesordrePrecision>
     */
    public function getDesordrePrecisions(): Collection
    {
        return $this->desordrePrecisions;
    }

    public function addDesordrePrecision(DesordrePrecision $desordrePrecision): static
    {
        if (!$this->desordrePrecisions->contains($desordrePrecision)) {
            $this->desordrePrecisions->add($desordrePrecision);
            $desordrePrecision->setDesordreCritere($this);
        }

        return $this;
    }

    public function removeDesordrePrecision(DesordrePrecision $desordrePrecision): static
    {
        if ($this->desordrePrecisions->removeElement($desordrePrecision)) {
            // set the owning side to null (unless already changed)
            if ($desordrePrecision->getDesordreCritere() === $this) {
                $desordrePrecision->setDesordreCritere(null);
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

    public function addSignalement(Signalement $signalement): static
    {
        if (!$this->signalement->contains($signalement)) {
            $this->signalement->add($signalement);
        }

        return $this;
    }

    public function removeSignalement(Signalement $signalement): static
    {
        $this->signalement->removeElement($signalement);

        return $this;
    }
}
