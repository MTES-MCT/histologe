<?php

namespace App\Entity;

use App\Entity\Behaviour\TimestampableTrait;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity('email', message: '{{ value }} existe déja, merci de saisir un nouvel e-mail')]
#[ORM\HasLifecycleCallbacks()]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    use TimestampableTrait;

    public const STATUS_INACTIVE = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_ARCHIVE = 2;
    public const STATUS_LABELS = [
        self::STATUS_INACTIVE => 'Inactif',
        self::STATUS_ACTIVE => 'Actif',
        self::STATUS_ARCHIVE => 'Archivé',
    ];

    public const MAX_LIST_PAGINATION = 20;

    public const ROLE_USAGER = self::ROLES['Usager'];
    public const ROLE_USER_PARTNER = self::ROLES['Utilisateur'];
    public const ROLE_ADMIN_PARTNER = self::ROLES['Administrateur'];
    public const ROLE_ADMIN_TERRITORY = self::ROLES['Responsable Territoire'];
    public const ROLE_ADMIN = self::ROLES['Super Admin'];

    public const SUFFIXE_ARCHIVED = '.archived@';
    public const ANONYMIZED_MAIL = 'anonyme@';
    public const ANONYMIZED_PRENOM = 'Utilisateur';
    public const ANONYMIZED_NOM = 'Anonymisé';

    public const ROLES = [
        'Usager' => 'ROLE_USAGER',
        'Utilisateur' => 'ROLE_USER_PARTNER',
        'Administrateur' => 'ROLE_ADMIN_PARTNER',
        'Responsable Territoire' => 'ROLE_ADMIN_TERRITORY',
        'Super Admin' => 'ROLE_ADMIN',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: Types::GUID)]
    private $uuid;

    #[ORM\Column(type: 'string', length: 180, unique: false)]
    #[Email(mode: Email::VALIDATION_MODE_STRICT, groups: ['registration'])]
    #[Assert\NotBlank(message: 'Merci de saisir une adresse e-mail.')]
    #[Assert\Length(max: 255)]
    private $email;

    #[ORM\Column(type: 'json')]
    private $roles = [];

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(groups: ['password'])]
    #[Assert\Length(min: 12, max: 200, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caratères.', groups: ['password'])]
    #[Assert\Regex(pattern: '/[A-Z]/', message: 'Le mot de passe doit contenir au moins une lettre majuscule.', groups: ['password'])]
    #[Assert\Regex(pattern: '/[a-z]/', message: 'Le mot de passe doit contenir au moins une lettre minuscule.', groups: ['password'])]
    #[Assert\Regex(pattern: '/[0-9]/', message: 'Le mot de passe doit contenir au moins un chiffre.', groups: ['password'])]
    #[Assert\Regex(pattern: '/[^a-zA-Z0-9]/', message: 'Le mot de passe doit contenir au moins un caractère spécial.', groups: ['password'])]
    #[Assert\NotCompromisedPassword(message: 'Ce mot de passe est compromis, veuillez en choisir un autre.', groups: ['password'])]
    #[Assert\NotEqualTo(propertyPath: 'email', message: 'Le mot de passe ne doit pas être votre e-mail.', groups: ['password'])]
    private $password;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $token = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $tokenExpiredAt = null;

    #[ORM\OneToMany(mappedBy: 'modifiedBy', targetEntity: Signalement::class)]
    private $signalementsModified;

    #[ORM\OneToMany(mappedBy: 'closedBy', targetEntity: Signalement::class)]
    private $signalementsClosed; // @phpstan-ignore-line

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Suivi::class, orphanRemoval: true)]
    private $suivis;

    #[ORM\ManyToOne(targetEntity: Partner::class, inversedBy: 'users', cascade: ['persist'])]
    private $partner;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Merci de saisir un nom.')]
    #[Assert\Length(max: 255)]
    private $nom;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Merci de saisir un prénom.')]
    #[Assert\Length(max: 255)]
    private $prenom;

    #[ORM\Column(type: 'integer')]
    private $statut;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $lastLoginAt;

    #[ORM\Column(type: 'boolean')]
    private $isMailingActive;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Notification::class)]
    private $notifications;

    #[ORM\ManyToOne(targetEntity: Territory::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true)]
    private $territory;

    #[ORM\OneToMany(mappedBy: 'uploadedBy', targetEntity: File::class)]
    private Collection $files;

    #[ORM\Column]
    private bool $isActivateAccountNotificationEnabled = true;

    #[ORM\OneToMany(mappedBy: 'declarant', targetEntity: SignalementUsager::class)]
    private $signalementUsagerDeclarants;

    #[ORM\OneToMany(mappedBy: 'occupant', targetEntity: SignalementUsager::class)]
    private $signalementUsagerOccupants;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeInterface $archivingScheduledAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $anonymizedAt = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $authCode;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $cguVersionChecked;

    public function __construct()
    {
        $this->suivis = new ArrayCollection();
        $this->statut = self::STATUS_INACTIVE;
        $this->notifications = new ArrayCollection();
        $this->uuid = Uuid::v4();
        $this->files = new ArrayCollection();
        $this->signalementUsagerDeclarants = new ArrayCollection();
        $this->signalementUsagerOccupants = new ArrayCollection();
    }

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
        return (string) $this->email;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
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
    public function eraseCredentials(): void
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
        return mb_strtoupper($this->nom ?? '').' '.ucfirst($this->prenom ?? '');
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

    public function getStatutLabel(): string
    {
        return self::STATUS_LABELS[$this->statut];
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function getLastLoginAtStr($format): string
    {
        if (!empty($this->lastLoginAt)) {
            return $this->lastLoginAt->format($format);
        }

        return '';
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): self
    {
        $this->lastLoginAt = $lastLoginAt;
        $this->archivingScheduledAt = null;

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

    public function getRoleLabel(): string
    {
        $roleLabel = array_flip(self::ROLES);
        $role = array_shift($this->roles);

        return $roleLabel[$role];
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function isSuperAdmin(): bool
    {
        return \in_array(self::ROLE_ADMIN, $this->roles);
    }

    public function isTerritoryAdmin(): bool
    {
        return \in_array(self::ROLE_ADMIN_TERRITORY, $this->roles);
    }

    public function isPartnerAdmin(): bool
    {
        return \in_array(self::ROLE_ADMIN_PARTNER, $this->getRoles());
    }

    public function isUserPartner(): bool
    {
        return \in_array(self::ROLE_USER_PARTNER, $this->getRoles());
    }

    public function isUsager(): bool
    {
        return \in_array(self::ROLE_USAGER, $this->getRoles());
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

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getTokenExpiredAt(): ?\DateTimeImmutable
    {
        return $this->tokenExpiredAt;
    }

    public function setTokenExpiredAt(?\DateTimeImmutable $tokenExpiredAt): self
    {
        $this->tokenExpiredAt = $tokenExpiredAt;

        return $this;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return Collection<int, File>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(File $file): self
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setUploadedBy($this);
        }

        return $this;
    }

    public function removeFile(File $file): self
    {
        if ($this->files->removeElement($file)) {
            // set the owning side to null (unless already changed)
            if ($file->getUploadedBy() === $this) {
                $file->setUploadedBy(null);
            }
        }

        return $this;
    }

    public function isActivateAccountNotificationEnabled(): bool
    {
        return $this->isActivateAccountNotificationEnabled;
    }

    public function setIsActivateAccountNotificationEnabled(bool $isActivateAccountNotificationEnabled): self
    {
        $this->isActivateAccountNotificationEnabled = $isActivateAccountNotificationEnabled;

        return $this;
    }

    public function getFullname(): string
    {
        return $this->prenom.' '.$this->nom;
    }

    public function getSignalementUsagerDeclarants(): Collection
    {
        return $this->signalementUsagerDeclarants;
    }

    public function getSignalementUsagerOccupants(): Collection
    {
        return $this->signalementUsagerOccupants;
    }

    public function getArchivingScheduledAt(): ?\DateTimeInterface
    {
        return $this->archivingScheduledAt;
    }

    public function setArchivingScheduledAt(?\DateTimeInterface $archivingScheduledAt): self
    {
        $this->archivingScheduledAt = $archivingScheduledAt;

        return $this;
    }

    public function getAnonymizedAt(): ?\DateTimeImmutable
    {
        return $this->anonymizedAt;
    }

    public function anonymize(): static
    {
        if (self::STATUS_ARCHIVE === $this->getStatut() && null === $this->anonymizedAt) {
            $this->setEmail(self::ANONYMIZED_MAIL.date('YmdHis').'.'.uniqid());
            $this->setPrenom(self::ANONYMIZED_PRENOM);
            $this->setNom(self::ANONYMIZED_NOM);
            $this->anonymizedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function isEmailAuthEnabled(): bool
    {
        return $this->isSuperAdmin();
    }

    public function getEmailAuthRecipient(): string
    {
        return $this->email;
    }

    public function getEmailAuthCode(): string
    {
        if (null === $this->authCode) {
            throw new \LogicException('The email authentication code was not set');
        }

        return $this->authCode;
    }

    public function setEmailAuthCode(string $authCode): void
    {
        $this->authCode = $authCode;
    }

    public function getCguVersionChecked(): ?string
    {
        return $this->cguVersionChecked;
    }

    public function setCguVersionChecked(string $cguVersionChecked): self
    {
        $this->cguVersionChecked = $cguVersionChecked;

        return $this;
    }
}
