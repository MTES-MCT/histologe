<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Behaviour\TimestampableTrait;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\Enum\SignalementStatus;
use App\Repository\UserRepository;
use App\Validator as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity('email', message: '{{ value }} existe déja, merci de saisir un nouvel e-mail')]
#[ORM\HasLifecycleCallbacks()]
#[AppAssert\UserPartnerEmailMulti(groups: ['user_partner_mail_multi'])]
#[AppAssert\UserPartnerEmail(groups: ['user_partner_mail'])]
class User implements UserInterface, EntityHistoryInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    use TimestampableTrait;

    public const int STATUS_INACTIVE = 0;
    public const int STATUS_ACTIVE = 1;
    public const int STATUS_ARCHIVE = 2;
    public const array STATUS_LABELS = [
        self::STATUS_INACTIVE => 'Inactif',
        self::STATUS_ACTIVE => 'Actif',
        self::STATUS_ARCHIVE => 'Archivé',
    ];

    public const string ROLE_API_USER = 'ROLE_API_USER';
    public const string ROLE_USAGER = self::ROLES['Usager'];
    public const string ROLE_USER_PARTNER = self::ROLES['Agent'];
    public const string ROLE_ADMIN_PARTNER = self::ROLES['Admin. partenaire'];
    public const string ROLE_ADMIN_TERRITORY = self::ROLES['Resp. Territoire'];
    public const string ROLE_ADMIN = self::ROLES['Super Admin'];
    public const string ROLE_USER = 'ROLE_USER';

    public const string SUFFIXE_ARCHIVED = '.archived@';
    public const string ANONYMIZED_MAIL = 'anonyme@';
    public const string ANONYMIZED_PRENOM = 'Utilisateur';
    public const string ANONYMIZED_NOM = 'Anonymisé';

    public const array ROLES = [
        'Usager' => 'ROLE_USAGER',
        'Agent' => 'ROLE_USER_PARTNER',
        'Admin. partenaire' => 'ROLE_ADMIN_PARTNER',
        'Resp. Territoire' => 'ROLE_ADMIN_TERRITORY',
        'Super Admin' => 'ROLE_ADMIN',
        'API' => 'ROLE_API_USER',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: Types::GUID)]
    private $uuid;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Email(mode: Email::VALIDATION_MODE_STRICT, groups: ['registration'])]
    #[Assert\NotBlank(message: 'Merci de saisir une adresse e-mail.')]
    #[Assert\Length(max: 255, groups: ['user_partner', 'Default'])]
    private $email;

    #[ORM\Column(type: 'json')]
    private $roles = [];

    #[ORM\Column]
    private bool $hasPermissionAffectation = false;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(groups: ['password'])]
    #[Assert\Length(min: 12, max: 200, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.', groups: ['password'])]
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

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Signalement::class)]
    private $signalementsCreated;

    #[ORM\OneToMany(mappedBy: 'closedBy', targetEntity: Signalement::class)]
    private $signalementsClosed; // @phpstan-ignore-line

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Suivi::class, orphanRemoval: true)]
    private $suivis;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Merci de saisir un nom.', groups: ['user_partner', 'Default'])]
    #[Assert\Length(max: 255, groups: ['user_partner', 'Default'])]
    private $nom;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Merci de saisir un prénom.', groups: ['user_partner', 'Default'])]
    #[Assert\Length(max: 255, groups: ['user_partner', 'Default'])]
    private $prenom;

    #[ORM\Column(type: 'integer')]
    private $statut;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $lastLoginAt;

    #[ORM\Column(type: 'boolean')]
    private $isMailingActive;

    #[ORM\Column(type: 'boolean')]
    #[Assert\NotNull(message: 'Merci de choisir une option de notification.')]
    private $isMailingSummary;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Notification::class)]
    private $notifications;

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
    #[Ignore]
    private ?string $authCode;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $cguVersionChecked;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarFilename = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tempEmail = null;

    #[ORM\OneToMany(mappedBy: 'ownedBy', targetEntity: ApiUserToken::class, cascade: ['persist'])]
    private Collection $apiUserTokens;

    /**
     * @var Collection<int, UserPartner>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserPartner::class, orphanRemoval: true)]
    private Collection $userPartners;

    /**
     * @var Collection<int, PopNotification>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: PopNotification::class, orphanRemoval: true)]
    private Collection $popNotifications;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 50, groups: ['user_partner', 'Default'])]
    private ?string $fonction = null;

    public function __construct()
    {
        $this->suivis = new ArrayCollection();
        $this->statut = self::STATUS_INACTIVE;
        $this->notifications = new ArrayCollection();
        $this->uuid = Uuid::v4();
        $this->files = new ArrayCollection();
        $this->signalementUsagerDeclarants = new ArrayCollection();
        $this->signalementUsagerOccupants = new ArrayCollection();
        $this->apiUserTokens = new ArrayCollection();
        $this->userPartners = new ArrayCollection();
        $this->popNotifications = new ArrayCollection();
        $this->isMailingSummary = true;
    }

    public function getId(): ?int
    {
        return $this->id;
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
     * @return Collection|Signalement[]
     */
    public function getSignalementsCreated(): Collection
    {
        return $this->signalementsCreated;
    }

    /**
     * @return Collection|Signalement[]
     */
    public function getSignalementsCreatedByStatut(SignalementStatus $statut): Collection
    {
        return $this->signalementsCreated->filter(function (Signalement $signalement) use ($statut) {
            return $statut === $signalement->getStatut();
        });
    }

    public function addSignalementCreated(Signalement $signalement): self
    {
        if (!$this->signalementsCreated->contains($signalement)) {
            $this->signalementsCreated[] = $signalement;
            $signalement->setCreatedBy($this);
        }

        return $this;
    }

    public function removeSignalementCreated(Signalement $signalement): self
    {
        if ($this->signalementsCreated->removeElement($signalement)) {
            // set the owning side to null (unless already changed)
            if ($signalement->getCreatedBy() === $this) {
                $signalement->setCreatedBy(null);
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

    /**
     * @return Collection<int, UserPartner>
     */
    public function getUserPartners(): Collection
    {
        return $this->userPartners;
    }

    /**
     * @return Collection<int, Partner>
     */
    public function getPartners(): Collection
    {
        $partners = new ArrayCollection();
        foreach ($this->userPartners as $userPartner) {
            $partners->add($userPartner->getPartner());
        }

        return $partners;
    }

    public function hasPartner(Partner $partner): bool
    {
        foreach ($this->userPartners as $userPartner) {
            if ($userPartner->getPartner() === $partner) {
                return true;
            }
        }

        return false;
    }

    public function addUserPartner(UserPartner $userPartner): static
    {
        if (!$this->userPartners->contains($userPartner)) {
            $this->userPartners->add($userPartner);
            $userPartner->setUser($this);
        }

        return $this;
    }

    public function removeUserPartner(UserPartner $userPartner): static
    {
        $this->userPartners->removeElement($userPartner);

        return $this;
    }

    public function getPartnersTerritories($forceLoadObject = false): array
    {
        $territories = [];
        foreach ($this->userPartners as $userPartner) {
            $territory = $userPartner->getPartner()->getTerritory();
            if ($forceLoadObject) {
                $territory->getZip();
            }
            if ($territory) {
                $territories[$territory->getId()] = $territory;
            }
        }

        return $territories;
    }

    public function getPartnerInTerritory(Territory $territory): ?Partner
    {
        foreach ($this->userPartners as $userPartner) {
            if ($userPartner->getPartner()->getTerritory()?->getId() === $territory->getId()) {
                return $userPartner->getPartner();
            }
        }

        return null;
    }

    public function getPartnerInTerritoryOrFirstOne(Territory $territory): ?Partner
    {
        return $this->getPartnerInTerritory($territory) ?? ($this->getPartners()->count() ? $this->getPartners()->first() : null);
    }

    public function hasPartnerInTerritory(Territory $territory): bool
    {
        foreach ($this->userPartners as $userPartner) {
            if ($userPartner->getPartner()->getTerritory()?->getId() === $territory->getId()) {
                return true;
            }
        }

        return false;
    }

    public function getFirstTerritory(): ?Territory
    {
        $territory = null;
        if ($this->userPartners->count()) {
            $territory = $this->userPartners->first()->getPartner()->getTerritory();
        }

        return $territory;
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

    public function getNomComplet($firstNameFirst = false)
    {
        if ($firstNameFirst) {
            return ucfirst($this->prenom ?? '').' '.mb_strtoupper($this->nom ?? '');
        }

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

    public function getIsMailingSummary(): bool
    {
        return $this->isMailingSummary;
    }

    public function setIsMailingSummary(?bool $isMailingSummary): self
    {
        $this->isMailingSummary = $isMailingSummary;

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

        if (isset($this->roles[0])) {
            $role = $this->roles[0];

            return $roleLabel[$role] ?? '';
        }

        return '';
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

    public function isApiUser(): bool
    {
        return \in_array(self::ROLE_API_USER, $this->getRoles());
    }

    public function isUsager(): bool
    {
        return \in_array(self::ROLE_USAGER, $this->getRoles());
    }

    public function hasPermissionAffectation(): bool
    {
        return $this->hasPermissionAffectation;
    }

    public function setHasPermissionAffectation(bool $hasPermissionAffectation): self
    {
        $this->hasPermissionAffectation = $hasPermissionAffectation;

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

    public function setEmailAuthCode(?string $authCode): void
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

    public function getAvatarFilename(): ?string
    {
        return $this->avatarFilename;
    }

    public function setAvatarFilename(?string $avatarFilename): self
    {
        $this->avatarFilename = $avatarFilename;

        return $this;
    }

    public function getTempEmail(): ?string
    {
        return $this->tempEmail;
    }

    public function setTempEmail(?string $tempEmail): self
    {
        $this->tempEmail = $tempEmail;

        return $this;
    }

    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }

    public function getApiUserTokens(): Collection
    {
        return $this->apiUserTokens;
    }

    public function addApiUserToken(ApiUserToken $apiUserToken): self
    {
        if (!$this->apiUserTokens->contains($apiUserToken)) {
            $this->apiUserTokens[] = $apiUserToken;
            $apiUserToken->setOwnedBy($this);
        }

        return $this;
    }

    public function removeApiUserToken(ApiUserToken $apiUserToken): self
    {
        if ($this->apiUserTokens->removeElement($apiUserToken)) {
            // set the owning side to null (unless already changed)
            if ($apiUserToken->getOwnedBy() === $this) {
                $apiUserToken->setOwnedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PopNotification>
     */
    public function getPopNotifications(): Collection
    {
        return $this->popNotifications;
    }

    public function addPopNotification(PopNotification $popNotification): static
    {
        if (!$this->popNotifications->contains($popNotification)) {
            $this->popNotifications->add($popNotification);
            $popNotification->setUser($this);
        }

        return $this;
    }

    public function removePopNotification(PopNotification $popNotification): static
    {
        if ($this->popNotifications->removeElement($popNotification)) {
            // set the owning side to null (unless already changed)
            if ($popNotification->getUser() === $this) {
                $popNotification->setUser(null);
            }
        }

        return $this;
    }

    public function getFonction(): ?string
    {
        return $this->fonction;
    }

    public function setFonction(?string $fonction): static
    {
        $this->fonction = $fonction;

        return $this;
    }
}
