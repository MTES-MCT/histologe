<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\HistoryEntryEvent;
use App\Repository\TerritoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TerritoryRepository::class)]
class Territory implements EntityHistoryInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['widget-settings:read', 'widget:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 3)]
    #[Groups(['widget-settings:read', 'widget:read'])]
    private ?string $zip = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['widget-settings:read', 'widget:read'])]
    private ?string $name = null;

    /** @var Collection<int, Partner> $partners */
    #[ORM\OneToMany(mappedBy: 'territory', targetEntity: Partner::class)]
    private Collection $partners;

    /** @var Collection<int, Commune> $communes */
    #[ORM\OneToMany(mappedBy: 'territory', targetEntity: Commune::class)]
    private Collection $communes;

    /** @var Collection<int, Signalement> $signalements */
    #[ORM\OneToMany(mappedBy: 'territory', targetEntity: Signalement::class)]
    private Collection $signalements;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive;

    /** @var Collection<int, Affectation> $affectations */
    #[ORM\OneToMany(mappedBy: 'territory', targetEntity: Affectation::class)]
    private Collection $affectations;

    /** @var array<mixed> $bbox */
    #[ORM\Column(type: 'json')]
    private array $bbox = [];

    /** @var Collection<int, Tag> $tags */
    #[ORM\OneToMany(mappedBy: 'territory', targetEntity: Tag::class, orphanRemoval: true)]
    private Collection $tags;

    /** @var array<mixed> $authorizedCodesInsee */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $authorizedCodesInsee = [];

    /** @var Collection<int, BailleurTerritory> $bailleurTerritories */
    #[ORM\OneToMany(mappedBy: 'territory', targetEntity: BailleurTerritory::class)]
    private Collection $bailleurTerritories;

    /** @var Collection<int, AutoAffectationRule> $autoAffectationRules */
    #[ORM\OneToMany(mappedBy: 'territory', targetEntity: AutoAffectationRule::class, orphanRemoval: true)]
    private Collection $autoAffectationRules;

    #[ORM\Column(length: 128)]
    private string $timezone;

    /** @var Collection<int, Zone> $zones */
    #[ORM\OneToMany(mappedBy: 'territory', targetEntity: Zone::class, orphanRemoval: true)]
    private Collection $zones;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $grilleVisiteFilename = null;

    #[ORM\Column]
    private ?bool $isGrilleVisiteDisabled = false;

    public function __construct()
    {
        $this->partners = new ArrayCollection();
        $this->signalements = new ArrayCollection();
        $this->affectations = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->bailleurTerritories = new ArrayCollection();
        $this->autoAffectationRules = new ArrayCollection();
        $this->communes = new ArrayCollection();
        $this->zones = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(string $zip): self
    {
        $this->zip = $zip;

        return $this;
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
     * @return Collection<int, Partner>
     */
    public function getPartners(): Collection
    {
        return $this->partners;
    }

    public function addPartner(Partner $partner): self
    {
        if (!$this->partners->contains($partner)) {
            $this->partners[] = $partner;
            $partner->setTerritory($this);
        }

        return $this;
    }

    public function removePartner(Partner $partner): self
    {
        if ($this->partners->removeElement($partner)) {
            // set the owning side to null (unless already changed)
            if ($partner->getTerritory() === $this) {
                $partner->setTerritory(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Commune>
     */
    public function getCommunes(): Collection
    {
        return $this->communes;
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
            $this->signalements[] = $signalement;
            $signalement->setTerritory($this);
        }

        return $this;
    }

    public function removeSignalement(Signalement $signalement): self
    {
        if ($this->signalements->removeElement($signalement)) {
            // set the owning side to null (unless already changed)
            if ($signalement->getTerritory() === $this) {
                $signalement->setTerritory(null);
            }
        }

        return $this;
    }

    public function isIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return Collection<int, Affectation>
     */
    public function getAffectations(): Collection
    {
        return $this->affectations;
    }

    public function addAffectation(Affectation $affectation): self
    {
        if (!$this->affectations->contains($affectation)) {
            $this->affectations[] = $affectation;
            $affectation->setTerritory($this);
        }

        return $this;
    }

    public function removeAffectation(Affectation $affectation): self
    {
        if ($this->affectations->removeElement($affectation)) {
            // set the owning side to null (unless already changed)
            if ($affectation->getTerritory() === $this) {
                $affectation->setTerritory(null);
            }
        }

        return $this;
    }

    /** @return array<mixed> */
    public function getBbox(): ?array
    {
        return $this->bbox;
    }

    /** @param array<mixed> $bbox */
    public function setBbox(array $bbox): self
    {
        $this->bbox = $bbox;

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
            $tag->setTerritory($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        if ($this->tags->removeElement($tag)) {
            // set the owning side to null (unless already changed)
            if ($tag->getTerritory() === $this) {
                $tag->setTerritory(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    /** @return array<string|int, mixed> */
    public function getAuthorizedCodesInsee(): ?array
    {
        return $this->authorizedCodesInsee;
    }

    /** @param array<string|int, mixed> $authorizedCodesInsee */
    public function setAuthorizedCodesInsee(?array $authorizedCodesInsee): self
    {
        $this->authorizedCodesInsee = $authorizedCodesInsee;

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
            $bailleurTerritory->setTerritory($this);
        }

        return $this;
    }

    public function removeBailleurTerritory(BailleurTerritory $bailleurTerritory): self
    {
        if ($this->bailleurTerritories->removeElement($bailleurTerritory)) {
            // set the owning side to null (unless already changed)
            if ($bailleurTerritory->getTerritory() === $this) {
                $bailleurTerritory->setTerritory(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AutoAffectationRule>
     */
    public function getAutoAffectationRules()
    {
        return $this->autoAffectationRules;
    }

    public function addAutoAffectationRule(AutoAffectationRule $autoAffectationRule): self
    {
        if (!$this->autoAffectationRules->contains($autoAffectationRule)) {
            $this->autoAffectationRules->add($autoAffectationRule);
            $autoAffectationRule->setTerritory($this);
        }

        return $this;
    }

    public function removeAutoAffectationRule(AutoAffectationRule $autoAffectationRule): self
    {
        if ($this->autoAffectationRules->removeElement($autoAffectationRule)) {
            // set the owning side to null (unless already changed)
            if ($autoAffectationRule->getTerritory() === $this) {
                $autoAffectationRule->setTerritory(null);
            }
        }

        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    /** @return array<HistoryEntryEvent> */
    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }

    /**
     * @return Collection<int, Zone>
     */
    public function getZones(): Collection
    {
        return $this->zones;
    }

    public function addZone(Zone $zone): static
    {
        if (!$this->zones->contains($zone)) {
            $this->zones->add($zone);
            $zone->setTerritory($this);
        }

        return $this;
    }

    public function removeZone(Zone $zone): static
    {
        if ($this->zones->removeElement($zone)) {
            // set the owning side to null (unless already changed)
            if ($zone->getTerritory() === $this) {
                $zone->setTerritory(null);
            }
        }

        return $this;
    }

    public function getGrilleVisiteFilename(): ?string
    {
        return $this->grilleVisiteFilename;
    }

    public function setGrilleVisiteFilename(?string $grilleVisiteFilename): static
    {
        $this->grilleVisiteFilename = $grilleVisiteFilename;

        return $this;
    }

    public function getIsGrilleVisiteDisabled(): ?bool
    {
        return $this->isGrilleVisiteDisabled;
    }

    public function setIsGrilleVisiteDisabled(bool $isGrilleVisiteDisabled): static
    {
        $this->isGrilleVisiteDisabled = $isGrilleVisiteDisabled;

        return $this;
    }
}
