<?php

namespace App\Entity;

use App\Repository\PartnerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartnerRepository::class)]
class Partner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $nom;

    #[ORM\OneToMany(mappedBy: 'partner', targetEntity: User::class)]
    private $users;

    #[ORM\Column(type: 'boolean')]
    private $isArchive;

    #[ORM\Column(type: 'boolean')]
    private $isCommune;

    #[ORM\Column(type: 'json')]
    private $insee = [];


    #[ORM\OneToMany(mappedBy: 'partner', targetEntity: Affectation::class, orphanRemoval: true)]
    private $affectations;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private $email;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $esaboraUrl;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $esaboraToken;

    #[ORM\ManyToOne(targetEntity: Territory::class, inversedBy: 'partners')]
    #[ORM\JoinColumn(nullable: true)]
    private $territory;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->isArchive = false;
        $this->affectations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId($id): ?int
    {
        return $this->id = $id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getUsers(): Collection
    {
        return $this->users->filter(function (User $user) {
            if ($user->getStatut() !== User::STATUS_ARCHIVE)
                return $user;
        });
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->setPartner($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getPartner() === $this) {
                $user->setPartner(null);
            }
        }

        return $this;
    }


    public function getIsArchive(): ?bool
    {
        return $this->isArchive;
    }

    public function setIsArchive(bool $isArchive): self
    {
        $this->isArchive = $isArchive;

        return $this;
    }

    public function getIsCommune(): ?bool
    {
        return $this->isCommune;
    }

    public function setIsCommune(bool $isCommune): self
    {
        $this->isCommune = $isCommune;

        return $this;
    }

    public function getInsee(): ?array
    {
        return $this->insee;
    }

    public function setInsee(?array $insee): self
    {
        $this->insee = $insee;

        return $this;
    }

    public function isAffected(Signalement $signalement)
    {
        $isAffected = $this->getAffectations()->filter(function (Affectation $affectation) use ($signalement) {
            if ($affectation->getSignalement()->getId() === $signalement->getId())
                return $signalement;
        });
        return !$isAffected->isEmpty();
    }

    /**
     * @return Collection|Affectation[]
     */
    public function getAffectations(): Collection
    {
        return $this->affectations;
    }

    public function hasGeneriqueUsers(): bool
    {
        foreach ($this->users as $user)
            if ($user->getIsGenerique())
                return true;
        return false;
    }

    public function addAffectation(Affectation $affectation): self
    {
        if (!$this->affectations->contains($affectation)) {
            $this->affectations[] = $affectation;
            $affectation->setPartner($this);
        }

        return $this;
    }

    public function removeAffectation(Affectation $affectation): self
    {
        if ($this->affectations->removeElement($affectation)) {
            // set the owning side to null (unless already changed)
            if ($affectation->getPartner() === $this) {
                $affectation->setPartner(null);
            }
        }

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEsaboraUrl(): ?string
    {
        return $this->esaboraUrl;
    }

    public function setEsaboraUrl(?string $esaboraUrl): self
    {
        $this->esaboraUrl = $esaboraUrl;

        return $this;
    }

    public function getEsaboraToken(): ?string
    {
        return $this->esaboraToken;
    }

    public function setEsaboraToken(?string $esaboraToken): self
    {
        $this->esaboraToken = $esaboraToken;

        return $this;
    }

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): self
    {
        $this->territory = $territory;

        return $this;
    }

}
