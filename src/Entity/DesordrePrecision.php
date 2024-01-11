<?php

namespace App\Entity;

use App\Entity\Behaviour\TimestampableTrait;
use App\Repository\DesordrePrecisionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DesordrePrecisionRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class DesordrePrecision
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $coef = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isDanger = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isSuroccupation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $label = null;

    #[ORM\Column]
    private array $qualification = [];

    #[ORM\ManyToOne(inversedBy: 'desordrePrecisions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DesordreCritere $desordreCritere = null;

    #[ORM\Column(length: 255)]
    private ?string $desordrePrecisionSlug = null;

    #[ORM\ManyToMany(targetEntity: Signalement::class, inversedBy: 'desordrePrecisions')]
    private Collection $signalement;

    public function __construct()
    {
        $this->signalement = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCoef(): ?float
    {
        return $this->coef;
    }

    public function setCoef(float $coef): self
    {
        $this->coef = $coef;

        return $this;
    }

    public function getIsDanger(): ?bool
    {
        return $this->isDanger;
    }

    public function setIsDanger(?bool $isDanger): self
    {
        $this->isDanger = $isDanger;

        return $this;
    }

    public function getIsSuroccupation(): ?bool
    {
        return $this->isSuroccupation;
    }

    public function setIsSuroccupation(?bool $isSuroccupation): self
    {
        $this->isSuroccupation = $isSuroccupation;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getQualification(): array
    {
        return $this->qualification;
    }

    public function setQualification(array $qualification): self
    {
        $this->qualification = $qualification;

        return $this;
    }

    public function getDesordreCritere(): ?DesordreCritere
    {
        return $this->desordreCritere;
    }

    public function setDesordreCritere(?DesordreCritere $desordreCritere): self
    {
        $this->desordreCritere = $desordreCritere;

        return $this;
    }

    public function getDesordrePrecisionSlug(): ?string
    {
        return $this->desordrePrecisionSlug;
    }

    public function setDesordrePrecisionSlug(string $desordrePrecisionSlug): self
    {
        $this->desordrePrecisionSlug = $desordrePrecisionSlug;

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
