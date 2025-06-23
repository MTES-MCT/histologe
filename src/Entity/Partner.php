<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Behaviour\TimestampableTrait;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\UserStatus;
use App\Repository\PartnerRepository;
use App\Utils\TrimHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PartnerRepository::class)]
#[ORM\HasLifecycleCallbacks()]
#[UniqueEntity(
    fields: ['email', 'territory', 'isArchive'],
    message: 'L\'e-mail de contact existe déjà pour ce territoire. Veuillez saisir un autre e-mail partenaire.',
    errorPath: 'email',
    ignoreNull: true)
]
class Partner implements EntityHistoryInterface
{
    use TimestampableTrait;

    public const string DEFAULT_PARTNER = 'Administrateurs Signal-logement';
    /** @var int[] */
    public const array OILHI_TERRITORY_ZIP_ALLOWED = [62, 55]; // Should be replaced by OILHI_CODE_INSEE_ALLOWED
    /** @var int[] */
    public const array OILHI_CODE_INSEE_ALLOWED = [62091, 55502, 55029, 55545]; // for testing production

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['widget-settings:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['widget-settings:read'])]
    private ?string $nom = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isArchive = false;

    #[ORM\Column(type: 'json')]
    private array $insee = [];

    #[ORM\OneToMany(mappedBy: 'partner', targetEntity: Affectation::class, orphanRemoval: true)]
    private Collection $affectations;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Email]
    #[Assert\Length(max: 255)]
    private ?string $email = null;

    #[ORM\Column]
    private ?bool $emailNotifiable = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $esaboraUrl = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $esaboraToken = null;

    #[ORM\ManyToOne(targetEntity: Territory::class, inversedBy: 'partners')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Territory $territory = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: PartnerType::class)]
    private ?PartnerType $type = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true, enumType: Qualification::class)]
    private array $competence = [];

    #[ORM\Column(nullable: true)]
    private ?bool $isEsaboraActive = null;

    #[ORM\OneToMany(mappedBy: 'partner', targetEntity: Intervention::class)]
    private Collection $interventions;

    #[ORM\Column]
    private ?bool $isIdossActive = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $idossUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $idossToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeInterface $idossTokenExpirationDate = null;

    #[ORM\ManyToOne(targetEntity: Bailleur::class, inversedBy: 'partners')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Bailleur $bailleur = null;

    /**
     * @var Collection<int, Zone>
     */
    #[ORM\ManyToMany(targetEntity: Zone::class, inversedBy: 'partners', cascade: ['persist'])]
    private Collection $zones;

    /**
     * @var Collection<int, Zone>
     */
    #[ORM\ManyToMany(targetEntity: Zone::class, inversedBy: 'excludedPartners', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'partner_excluded_zone')]
    private Collection $excludedZones;

    /**
     * @var Collection<int, UserPartner>
     */
    #[ORM\OneToMany(mappedBy: 'partner', targetEntity: UserPartner::class, orphanRemoval: true)]
    private Collection $userPartners;

    public function __construct()
    {
        $this->isArchive = false;
        $this->affectations = new ArrayCollection();
        $this->interventions = new ArrayCollection();
        $this->isIdossActive = false;
        $this->zones = new ArrayCollection();
        $this->userPartners = new ArrayCollection();
        $this->excludedZones = new ArrayCollection();
        $this->emailNotifiable = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId($id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = TrimHelper::safeTrim($nom);

        return $this;
    }

    /**
     * @return Collection<int, UserPartner>
     */
    public function getUserPartners(): Collection
    {
        return $this->userPartners->filter(function (UserPartner $userPartner) {
            return UserStatus::ARCHIVE !== $userPartner->getUser()->getStatut();
        });
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        $users = new ArrayCollection();
        foreach ($this->getUserPartners() as $userPartner) {
            $users->add($userPartner->getUser());
        }

        return $users;
    }

    public function addUserPartner(UserPartner $userPartner): static
    {
        if (!$this->userPartners->contains($userPartner)) {
            $this->userPartners->add($userPartner);
            $userPartner->setPartner($this);
        }

        return $this;
    }

    public function removeUserPartner(UserPartner $userPartner): static
    {
        $this->userPartners->removeElement($userPartner);

        return $this;
    }

    public function getIsArchive(): ?bool
    {
        return $this->isArchive;
    }

    public function setIsArchive(bool $isArchive): static
    {
        $this->isArchive = $isArchive;

        return $this;
    }

    public function getInsee(): array
    {
        return $this->insee;
    }

    public function setInsee(array $insee): static
    {
        $this->insee = $insee;

        return $this;
    }

    /**
     * @return Collection|Affectation[]
     */
    public function getAffectations(): Collection
    {
        return $this->affectations;
    }

    public function removeAffectation(Affectation $affectation): static
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

    public function setEmail(?string $email): static
    {
        $this->email = !empty($email) ? mb_strtolower($email) : null;

        return $this;
    }

    public function isEmailNotifiable(): ?bool
    {
        return $this->emailNotifiable;
    }

    public function setEmailNotifiable(bool $emailNotifiable): static
    {
        $this->emailNotifiable = $emailNotifiable;

        return $this;
    }

    public function receiveEmailNotifications(?User $excludeUser = null): bool
    {
        if ($this->email && $this->emailNotifiable) {
            return true;
        }
        foreach ($this->getUsers() as $user) {
            if ($excludeUser && $user->getId() === $excludeUser->getId()) {
                continue;
            }
            if (UserStatus::ACTIVE === $user->getStatut() && $user->getIsMailingActive()) {
                return true;
            }
        }

        return false;
    }

    public function getEsaboraUrl(): ?string
    {
        return $this->esaboraUrl;
    }

    public function setEsaboraUrl(?string $esaboraUrl): static
    {
        $this->esaboraUrl = $esaboraUrl;

        return $this;
    }

    public function getEsaboraToken(): ?string
    {
        return $this->esaboraToken;
    }

    public function setEsaboraToken(?string $esaboraToken): static
    {
        $this->esaboraToken = $esaboraToken;

        return $this;
    }

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): static
    {
        $this->territory = $territory;

        return $this;
    }

    public function getEsaboraCredential(): array
    {
        return [
            $this->esaboraUrl,
            $this->esaboraToken,
        ];
    }

    public function getType(): ?PartnerType
    {
        return $this->type;
    }

    public function setType(PartnerType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getIsCommune(): ?bool
    {
        return PartnerType::COMMUNE_SCHS === $this->type;
    }

    public function getCompetence(): ?array
    {
        return $this->competence;
    }

    public function setCompetence(?array $competence): static
    {
        $this->competence = $competence;

        return $this;
    }

    public function hasCompetence(Qualification $qualification): bool
    {
        return \in_array($qualification, $this->getCompetence());
    }

    public function isEsaboraActive(): ?bool
    {
        return $this->isEsaboraActive;
    }

    public function setIsEsaboraActive(?bool $isEsaboraActive): static
    {
        $this->isEsaboraActive = $isEsaboraActive;

        return $this;
    }

    public function getInterventions(): Collection
    {
        return $this->interventions;
    }

    public function getEmailActiveUsers(): array
    {
        $emailUsers = [];
        foreach ($this->getUsers() as $user) {
            $emailUsers[] = $user->getEmail();
        }

        return array_filter($emailUsers);
    }

    public function canSyncWithEsabora(): bool
    {
        return $this->esaboraToken
            && $this->esaboraUrl
            && $this->isEsaboraActive;
    }

    public function canSyncWithOilhi(Signalement $signalement): bool
    {
        $inseeAllowed = !empty(array_intersect($this->getInsee(), self::OILHI_CODE_INSEE_ALLOWED));

        return $this->hasCompetence(Qualification::RSD)
            && PartnerType::COMMUNE_SCHS === $this->type
            && \in_array(
                $this->territory->getZip(),
                self::OILHI_TERRITORY_ZIP_ALLOWED
            )
            && $inseeAllowed
            && in_array($signalement->getInseeOccupant(), self::OILHI_CODE_INSEE_ALLOWED);
    }

    public function canSyncWithIdoss(): bool
    {
        return $this->isIdossActive && $this->idossUrl;
    }

    public function isIdossActive(): ?bool
    {
        return $this->isIdossActive;
    }

    public function setIsIdossActive(bool $isIdossActive): static
    {
        $this->isIdossActive = $isIdossActive;

        return $this;
    }

    public function getIdossUrl(): ?string
    {
        if (isset($this->idossUrl) && str_ends_with($this->idossUrl, '/')) {
            return substr($this->idossUrl, 0, -1);
        }

        return $this->idossUrl;
    }

    public function setIdossUrl(?string $idossUrl): static
    {
        $this->idossUrl = $idossUrl;

        return $this;
    }

    public function getIdossToken(): ?string
    {
        return $this->idossToken;
    }

    public function setIdossToken(?string $idossToken): static
    {
        $this->idossToken = $idossToken;

        return $this;
    }

    public function getIdossTokenExpirationDate(): ?\DateTimeInterface
    {
        return $this->idossTokenExpirationDate;
    }

    public function setIdossTokenExpirationDate(?\DateTimeInterface $idossTokenExpirationDate): static
    {
        $this->idossTokenExpirationDate = $idossTokenExpirationDate;

        return $this;
    }

    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
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
        }

        return $this;
    }

    public function removeZone(Zone $zone): static
    {
        $this->zones->removeElement($zone);

        return $this;
    }

    /**
     * @return Collection<int, Zone>
     */
    public function getExcludedZones(): Collection
    {
        return $this->excludedZones;
    }

    public function addExcludedZone(Zone $excludedZone): static
    {
        if (!$this->excludedZones->contains($excludedZone)) {
            $this->excludedZones->add($excludedZone);
        }

        return $this;
    }

    public function removeExcludedZone(Zone $excludedZone): static
    {
        $this->excludedZones->removeElement($excludedZone);

        return $this;
    }

    public function getBailleur(): ?Bailleur
    {
        return $this->bailleur;
    }

    public function setBailleur(?Bailleur $bailleur): static
    {
        $this->bailleur = $bailleur;

        return $this;
    }
}
