<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Behaviour\TimestampableTrait;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\UserStatus;
use App\Repository\UserRepository;
use App\Utils\Phone;
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
#[UniqueEntity('email', message: '{{ value }} existe déja, merci de saisir un nouvel e-mail', groups: ['registration', 'Default'])]
#[ORM\HasLifecycleCallbacks()]
#[AppAssert\UserPartnerEmailMulti(groups: ['user_partner_mail_multi'])]
#[AppAssert\UserPartnerEmail(groups: ['user_partner_mail'])]
class User implements UserInterface, EntityHistoryInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    use TimestampableTrait;

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

    /** @var array<string, string> ROLES */
    public const array ROLES = [
        'Usager' => 'ROLE_USAGER',
        'Agent' => 'ROLE_USER_PARTNER',
        'Admin. partenaire' => 'ROLE_ADMIN_PARTNER',
        'Resp. Territoire' => 'ROLE_ADMIN_TERRITORY',
        'Super Admin' => 'ROLE_ADMIN',
        'API' => 'ROLE_API_USER',
    ];

    public const string MSG_SUBSCRIPTION_CREATED = 'Vous avez rejoint le dossier et recevez désormais les notifications le concernant.';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID, unique: true)]
    private ?string $uuid = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $proConnectUserId = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Email(mode: Email::VALIDATION_MODE_STRICT, groups: ['registration', 'Default'])]
    #[Assert\NotBlank(message: 'Merci de saisir une adresse e-mail.')]
    #[Assert\Length(max: 255, groups: ['user_partner', 'Default'])]
    private ?string $email = null;

    /** @var array<mixed> $roles */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

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
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $token = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $tokenExpiredAt = null;

    /** @var Collection<int, Signalement> $signalementsModified */
    #[ORM\OneToMany(mappedBy: 'modifiedBy', targetEntity: Signalement::class)]
    private Collection $signalementsModified;

    /** @var Collection<int, Signalement> $signalementsCreated */
    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Signalement::class)]
    private Collection $signalementsCreated;

    /** @var Collection<int, Signalement> $signalementsClosed */
    #[ORM\OneToMany(mappedBy: 'closedBy', targetEntity: Signalement::class)]
    private Collection $signalementsClosed; // @phpstan-ignore-line

    /** @var Collection<int, Suivi> $suivis */
    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Suivi::class, orphanRemoval: true)]
    private Collection $suivis;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Merci de saisir un nom.', groups: ['user_partner', 'Default'])]
    #[Assert\Length(max: 255, groups: ['user_partner', 'Default'])]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Merci de saisir un prénom.', groups: ['user_partner', 'Default'])]
    #[Assert\Length(max: 255, groups: ['user_partner', 'Default'])]
    private ?string $prenom = null;

    #[ORM\Column(type: 'string', enumType: UserStatus::class)]
    private ?UserStatus $statut = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isMailingActive = true;

    #[ORM\Column(type: 'boolean')]
    #[Assert\NotNull(message: 'Merci de choisir une option de notification.')]
    private ?bool $isMailingSummary;

    /** @var Collection<int, Notification> $notifications */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Notification::class)]
    private Collection $notifications;

    /** @var Collection<int, File> $files */
    #[ORM\OneToMany(mappedBy: 'uploadedBy', targetEntity: File::class)]
    private Collection $files;

    #[ORM\Column]
    private bool $isActivateAccountNotificationEnabled = true;

    /** @var Collection<int, SignalementUsager> $signalementUsagerDeclarants */
    #[ORM\OneToMany(mappedBy: 'declarant', targetEntity: SignalementUsager::class)]
    private Collection $signalementUsagerDeclarants;

    /** @var Collection<int, SignalementUsager> $signalementUsagerOccupants */
    #[ORM\OneToMany(mappedBy: 'occupant', targetEntity: SignalementUsager::class)]
    private Collection $signalementUsagerOccupants;

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

    /** @var Collection<int, ApiUserToken> $apiUserTokens */
    #[ORM\OneToMany(mappedBy: 'ownedBy', targetEntity: ApiUserToken::class, cascade: ['persist'])]
    private Collection $apiUserTokens;

    /** @var Collection<int, UserPartner> $userPartners */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserPartner::class, orphanRemoval: true)]
    private Collection $userPartners;

    /** @var Collection<int, PopNotification> $popNotifications */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: PopNotification::class, orphanRemoval: true)]
    private Collection $popNotifications;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 50, groups: ['user_partner', 'Default'])]
    private ?string $fonction = null;

    private bool $isAuthenticatedViaProConnect = false;

    /**
     * @var Collection<int, UserSignalementSubscription>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserSignalementSubscription::class, orphanRemoval: true)]
    private Collection $userSignalementSubscriptions;

    #[ORM\Column]
    private ?bool $hasDoneSubscriptionsChoice = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $duplicateModalDismissedAt = null;

    /**
     * @var Collection<int, UserApiPermission>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserApiPermission::class, orphanRemoval: true)]
    private Collection $userApiPermissions;

    #[ORM\Column(length: 50, nullable: true)]
    #[AppAssert\TelephoneFormat(groups: ['user_partner', 'Default'])]
    private ?string $phone = null;

    public function __construct()
    {
        $this->suivis = new ArrayCollection();
        $this->statut = UserStatus::INACTIVE;
        $this->notifications = new ArrayCollection();
        $this->uuid = Uuid::v4();
        $this->files = new ArrayCollection();
        $this->signalementUsagerDeclarants = new ArrayCollection();
        $this->signalementUsagerOccupants = new ArrayCollection();
        $this->apiUserTokens = new ArrayCollection();
        $this->userPartners = new ArrayCollection();
        $this->popNotifications = new ArrayCollection();
        $this->isMailingSummary = true;
        $this->userSignalementSubscriptions = new ArrayCollection();
        $this->hasDoneSubscriptionsChoice = false;
        $this->userApiPermissions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProConnectUserId(): ?string
    {
        return $this->proConnectUserId;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function setProConnectUserId(?string $proConnectUserId): static
    {
        $this->proConnectUserId = $proConnectUserId;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     *
     * @return non-empty-string
     */
    public function getUserIdentifier(): string
    {
        if (null === $this->email || '' === $this->email) {
            throw new \LogicException('User email is not set');
        }

        return $this->email;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
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
     * @return Collection<int, Signalement>
     */
    public function getSignalementsModified(): Collection
    {
        return $this->signalementsModified;
    }

    public function addSignalementModified(Signalement $signalement): static
    {
        if (!$this->signalementsModified->contains($signalement)) {
            $this->signalementsModified[] = $signalement;
            $signalement->setModifiedBy($this);
        }

        return $this;
    }

    public function removeSignalementModified(Signalement $signalement): static
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
     * @return Collection<int, Signalement>
     */
    public function getSignalementsCreated(): Collection
    {
        return $this->signalementsCreated;
    }

    /**
     * @return Collection<int, Signalement>
     */
    public function getSignalementsCreatedByStatut(SignalementStatus $statut): Collection
    {
        return $this->signalementsCreated->filter(function (Signalement $signalement) use ($statut) {
            return $statut === $signalement->getStatut();
        });
    }

    public function addSignalementCreated(Signalement $signalement): static
    {
        if (!$this->signalementsCreated->contains($signalement)) {
            $this->signalementsCreated[] = $signalement;
            $signalement->setCreatedBy($this);
        }

        return $this;
    }

    public function removeSignalementCreated(Signalement $signalement): static
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
     * @return Collection<int, Suivi>
     */
    public function getSuivis(): Collection
    {
        return $this->suivis;
    }

    public function addSuivi(Suivi $suivi): static
    {
        if (!$this->suivis->contains($suivi)) {
            $this->suivis[] = $suivi;
            $suivi->setCreatedBy($this);
        }

        return $this;
    }

    public function removeSuivi(Suivi $suivi): static
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

    public function isAloneInPartner(?Partner $partner): bool
    {
        if ($partner && $this->hasPartner($partner)) {
            return 1 === $partner->getUsers()->count();
        }
        foreach ($this->getPartners() as $partner) {
            // from the moment the agent is alone in one of its partners, it is considered alone for initialization
            if ($partner->getUsers()->count() <= 1) {
                return true;
            }
        }

        return false;
    }

    public function removeUserPartner(UserPartner $userPartner): static
    {
        $this->userPartners->removeElement($userPartner);

        return $this;
    }

    /** @return array<Territory> */
    public function getPartnersTerritories(bool $forceLoadObject = false): array
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
        $partnerInTerritory = $this->getPartnerInTerritory($territory);
        if (null !== $partnerInTerritory) {
            return $partnerInTerritory;
        }

        $first = $this->getPartners()->first();

        return $first instanceof Partner ? $first : null;
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
            $first = $this->userPartners->first();
            if ($first instanceof UserPartner) {
                $territory = $first->getPartner()->getTerritory();
            }
        }

        return $territory;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNomComplet(bool $firstNameFirst = false): string
    {
        if ($firstNameFirst) {
            return ucfirst($this->prenom ?? '').' '.mb_strtoupper($this->nom ?? '');
        }

        return mb_strtoupper($this->nom ?? '').' '.ucfirst($this->prenom ?? '');
    }

    public function getStatut(): ?UserStatus
    {
        return $this->statut;
    }

    public function setStatut(UserStatus $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function getLastLoginAtStr(string $format): string
    {
        if (!empty($this->lastLoginAt)) {
            return $this->lastLoginAt->format($format);
        }

        return '';
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        $this->archivingScheduledAt = null;

        return $this;
    }

    public function getIsMailingActive(): ?bool
    {
        return $this->isMailingActive;
    }

    public function setIsMailingActive(bool $isMailingActive): static
    {
        $this->isMailingActive = $isMailingActive;

        return $this;
    }

    public function getIsMailingSummary(): ?bool
    {
        return $this->isMailingSummary;
    }

    public function setIsMailingSummary(?bool $isMailingSummary): static
    {
        $this->isMailingSummary = $isMailingSummary;

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications[] = $notification;
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
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

    /** @param array<mixed> $roles */
    public function setRoles(array $roles): static
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

    public function setHasPermissionAffectation(bool $hasPermissionAffectation): static
    {
        $this->hasPermissionAffectation = $hasPermissionAffectation;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getTokenExpiredAt(): ?\DateTimeImmutable
    {
        return $this->tokenExpiredAt;
    }

    public function setTokenExpiredAt(?\DateTimeImmutable $tokenExpiredAt): static
    {
        $this->tokenExpiredAt = $tokenExpiredAt;

        return $this;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
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

    public function addFile(File $file): static
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setUploadedBy($this);
        }

        return $this;
    }

    public function removeFile(File $file): static
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

    public function setIsActivateAccountNotificationEnabled(bool $isActivateAccountNotificationEnabled): static
    {
        $this->isActivateAccountNotificationEnabled = $isActivateAccountNotificationEnabled;

        return $this;
    }

    public function getFullname(): string
    {
        return $this->prenom.' '.$this->nom;
    }

    /** @return Collection<int, SignalementUsager> */
    public function getSignalementUsagerDeclarants(): Collection
    {
        return $this->signalementUsagerDeclarants;
    }

    /** @return Collection<int, SignalementUsager> */
    public function getSignalementUsagerOccupants(): Collection
    {
        return $this->signalementUsagerOccupants;
    }

    public function getArchivingScheduledAt(): ?\DateTimeInterface
    {
        return $this->archivingScheduledAt;
    }

    public function setArchivingScheduledAt(?\DateTimeInterface $archivingScheduledAt): static
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
        if (UserStatus::ARCHIVE === $this->getStatut() && null === $this->anonymizedAt) {
            $this->setEmail(self::ANONYMIZED_MAIL.date('YmdHis').'.'.uniqid());
            $this->setPrenom(self::ANONYMIZED_PRENOM);
            $this->setNom(self::ANONYMIZED_NOM);
            $this->anonymizedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function isEmailAuthEnabled(): bool
    {
        return $this->isSuperAdmin() && !$this->isAuthenticatedViaProConnect;
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

    public function setCguVersionChecked(string $cguVersionChecked): static
    {
        $this->cguVersionChecked = $cguVersionChecked;

        return $this;
    }

    public function getAvatarFilename(): ?string
    {
        return $this->avatarFilename;
    }

    public function setAvatarFilename(?string $avatarFilename): static
    {
        $this->avatarFilename = $avatarFilename;

        return $this;
    }

    public function getTempEmail(): ?string
    {
        return $this->tempEmail;
    }

    public function setTempEmail(?string $tempEmail): static
    {
        $this->tempEmail = $tempEmail;

        return $this;
    }

    /** @return array<HistoryEntryEvent> */
    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }

    /** @return Collection<int, ApiUserToken> */
    public function getApiUserTokens(): Collection
    {
        return $this->apiUserTokens;
    }

    public function addApiUserToken(ApiUserToken $apiUserToken): static
    {
        if (!$this->apiUserTokens->contains($apiUserToken)) {
            $this->apiUserTokens[] = $apiUserToken;
            $apiUserToken->setOwnedBy($this);
        }

        return $this;
    }

    public function removeApiUserToken(ApiUserToken $apiUserToken): static
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

    public function isAuthenticatedViaProConnect(): bool
    {
        return $this->isAuthenticatedViaProConnect;
    }

    public function setIsAuthenticatedViaProConnect(bool $isAuthenticatedViaProConnect): void
    {
        $this->isAuthenticatedViaProConnect = $isAuthenticatedViaProConnect;
    }

    /**
     * @return Collection<int, UserSignalementSubscription>
     */
    public function getUserSignalementSubscriptions(): Collection
    {
        return $this->userSignalementSubscriptions;
    }

    public function addUserSignalementSubscription(UserSignalementSubscription $userSignalementSubscription): static
    {
        if (!$this->userSignalementSubscriptions->contains($userSignalementSubscription)) {
            $this->userSignalementSubscriptions->add($userSignalementSubscription);
            $userSignalementSubscription->setUser($this);
        }

        return $this;
    }

    public function removeUserSignalementSubscription(UserSignalementSubscription $userSignalementSubscription): static
    {
        if ($this->userSignalementSubscriptions->removeElement($userSignalementSubscription)) {
            // set the owning side to null (unless already changed)
            if ($userSignalementSubscription->getUser() === $this) {
                $userSignalementSubscription->setUser(null);
            }
        }

        return $this;
    }

    public function hasDoneSubscriptionsChoice(): ?bool
    {
        return $this->hasDoneSubscriptionsChoice;
    }

    public function setHasDoneSubscriptionsChoice(bool $hasDoneSubscriptionsChoice): static
    {
        $this->hasDoneSubscriptionsChoice = $hasDoneSubscriptionsChoice;

        return $this;
    }

    public function isDuplicateModalDismissed(): bool
    {
        return null !== $this->duplicateModalDismissedAt;
    }

    public function setDuplicateModalDismissed(): static
    {
        $this->duplicateModalDismissedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * @return Collection<int, UserApiPermission>
     */
    public function getUserApiPermissions(): Collection
    {
        return $this->userApiPermissions;
    }

    public function addUserApiPermission(UserApiPermission $userApiPermission): static
    {
        if (!$this->userApiPermissions->contains($userApiPermission)) {
            $this->userApiPermissions->add($userApiPermission);
            $userApiPermission->setUser($this);
        }

        return $this;
    }

    public function removeUserApiPermission(UserApiPermission $userApiPermission): static
    {
        if ($this->userApiPermissions->removeElement($userApiPermission)) {
            // set the owning side to null (unless already changed)
            if ($userApiPermission->getUser() === $this) {
                $userApiPermission->setUser(null);
            }
        }

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPhoneDecoded(): ?string
    {
        return Phone::format($this->phone);
    }

    public function isMultiTerritoire(): bool
    {
        return count($this->getPartnersTerritories()) > 1;
    }
}
