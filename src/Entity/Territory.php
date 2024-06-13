<?php

namespace App\Entity;

use App\Repository\TerritoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TerritoryRepository::class)]
class Territory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['widget:read'])]
    private $id;

    #[ORM\Column(type: 'string', length: 3)]
    #[Groups(['widget:read'])]
    private $zip;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['widget:read'])]
    private $name;

    #[ORM\OneToMany(mappedBy: 'territory', targetEntity: User::class)]
    private $users;

    #[ORM\OneToMany(mappedBy: 'territory', targetEntity: Partner::class)]
    private $partners;

    #[ORM\OneToMany(mappedBy: 'territory', targetEntity: Commune::class)]
    private $communes;

    #[ORM\OneToMany(mappedBy: 'territory', targetEntity: Signalement::class)]
    private $signalements;

    #[ORM\Column(type: 'boolean')]
    private $isActive;

    #[ORM\OneToMany(mappedBy: 'territory', targetEntity: Affectation::class)]
    private $affectations;

    #[ORM\Column(type: 'json')]
    private $bbox = [];

    #[ORM\OneToMany(mappedBy: 'territory', targetEntity: Tag::class, orphanRemoval: true)]
    private $tags;

    #[ORM\Column(type: 'json', nullable: true)]
    private $authorizedCodesInsee = [];

    #[ORM\Column]
    private ?bool $isAutoAffectationEnabled = false;

    #[ORM\OneToMany(mappedBy: 'territory', targetEntity: BailleurTerritory::class)]
    private Collection $bailleurTerritories;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->partners = new ArrayCollection();
        $this->signalements = new ArrayCollection();
        $this->affectations = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->bailleurTerritories = new ArrayCollection();
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
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->setTerritory($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getTerritory() === $this) {
                $user->setTerritory(null);
            }
        }

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

    public function getBbox(): ?array
    {
        return $this->bbox;
    }

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

    public function getAuthorizedCodesInsee(): ?array
    {
        return $this->authorizedCodesInsee;
    }

    public function setAuthorizedCodesInsee(?array $authorizedCodesInsee): self
    {
        $this->authorizedCodesInsee = $authorizedCodesInsee;

        return $this;
    }

    public function isAutoAffectationEnabled(): ?bool
    {
        return $this->isAutoAffectationEnabled;
    }

    public function setIsAutoAffectationEnabled(bool $isAutoAffectationEnabled): self
    {
        $this->isAutoAffectationEnabled = $isAutoAffectationEnabled;

        return $this;
    }

    /**
     * @return Collection<int, BailleurTerritory>
     */
    public function getBailleurTerritories(): Collection
    {
        return $this->bailleurTerritories;
    }

    public function addBailleurTerritory(BailleurTerritory $bailleurTerritory): static
    {
        if (!$this->bailleurTerritories->contains($bailleurTerritory)) {
            $this->bailleurTerritories->add($bailleurTerritory);
            $bailleurTerritory->setTerritory($this);
        }

        return $this;
    }

    public function removeBailleurTerritory(BailleurTerritory $bailleurTerritory): static
    {
        if ($this->bailleurTerritories->removeElement($bailleurTerritory)) {
            // set the owning side to null (unless already changed)
            if ($bailleurTerritory->getTerritory() === $this) {
                $bailleurTerritory->setTerritory(null);
            }
        }

        return $this;
    }
}
