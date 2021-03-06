<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_ARCHIVE = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 180, unique: false)]
    private $email;

    #[ORM\Column(type: 'json')]
    private $roles = [];

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 200, minMessage: "Votre mot de passe doit contenir au moins {{ limit }} caratères")]
    #[Assert\NotCompromisedPassword(message: "Ce mot de passe est compromis, veuillez en choisir un autre.")]
    #[Assert\NotEqualTo(propertyPath: 'email', message: "Votre mot de passe ne doit pas contenir votre email.")]
    #[Assert\NotEqualTo(propertyPath: 'histologe', message: "Votre mot de passe ne doit pas contenir 'histologe'")]
    private $password;

    #[ORM\OneToMany(mappedBy: 'modifiedBy', targetEntity: Signalement::class)]
    private $signalementsModified;


    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Suivi::class, orphanRemoval: true)]
    private $suivis;

    #[ORM\ManyToOne(targetEntity: Partner::class, inversedBy: 'users')]
    private $partner;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $nom;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $prenom;


    #[ORM\Column(type: 'integer')]
    private $statut;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $lastLoginAt;

    private $newsActivitiesSinceLastLogin;

    #[ORM\Column(type: 'boolean')]
    private $isGenerique;

    #[ORM\Column(type: 'boolean')]
    private $isMailingActive;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Notification::class)]
    private $notifications;

    #[ORM\ManyToOne(targetEntity: Territory::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true)]
    private $territory;

    public function __construct()
    {
        $this->suivis = new ArrayCollection();
        $this->statut = self::STATUS_INACTIVE;
        $this->notifications = new ArrayCollection();
    }

    /*public function setId($id): ?self
    {
        $this->id = $id;
        return $this;
    }*/

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(mixed $id_userbo)
    {
        $this->id = $id_userbo;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string|null
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection|Signalement[]
     */
    public function getSignalementsModified(): Collection
    {
        return $this->signalementsModified;
    }

    public function addSignalementModified(Signalement $signalement): self
    {
        if (!$this->signalementsModified->contains($signalement)) {
            $this->signalementsModified[] = $signalement;
            $signalement->setModifiedBy($this);
        }

        return $this;
    }

    public function removeSignalementModified(Signalement $signalement): self
    {
        if ($this->signalementsModified->removeElement($signalement)) {
            // set the owning side to null (unless already changed)
            if ($signalement->getModifiedBy() === $this) {
                $signalement->setModifiedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Suivi[]
     */
    public function getSuivis(): Collection
    {
        return $this->suivis;
    }

    public function addSuivi(Suivi $suivi): self
    {
        if (!$this->suivis->contains($suivi)) {
            $this->suivis[] = $suivi;
            $suivi->setCreatedBy($this);
        }

        return $this;
    }

    public function removeSuivi(Suivi $suivi): self
    {
        if ($this->suivis->removeElement($suivi)) {
            // set the owning side to null (unless already changed)
            if ($suivi->getCreatedBy() === $this) {
                $suivi->setCreatedBy(null);
            }
        }

        return $this;
    }

    public function getPartner(): ?Partner
    {
        return $this->partner;
    }

    public function setPartner(?Partner $partner): self
    {
        $this->partner = $partner;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNomComplet()
    {
        return mb_strtoupper($this->nom) . ' ' . ucfirst($this->prenom);
    }

    public function getStatut(): ?int
    {
        return $this->statut;
    }

    public function setStatut(int $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getLastLoginAt(): ?DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?DateTimeImmutable $lastLoginAt): self
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function getIsGenerique(): ?bool
    {
        return $this->isGenerique;
    }

    public function setIsGenerique(bool $isGenerique): self
    {
        $this->isGenerique = $isGenerique;

        return $this;
    }

    public function getIsMailingActive(): ?bool
    {
        return $this->isMailingActive;
    }

    public function setIsMailingActive(bool $isMailingActive): self
    {
        $this->isMailingActive = $isMailingActive;

        return $this;
    }

    /**
     * @return Collection|Notification[]
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): self
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications[] = $notification;
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): self
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }

    public function isPartnerAdmin(): bool
    {
        return in_array('ROLE_ADMIN_PARTNER', $this->getRoles());
//        return count(array_intersect(['ROLE_ADMIN_TERRITOIRE','ROLE_ADMIN_PARTNER'], $this->roles)) > 0;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function isSuperAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles);
    }

    public function isTerritoryAdmin(): bool
    {
        return in_array('ROLE_ADMIN_TERRITORY', $this->roles);
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
