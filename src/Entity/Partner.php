<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Behaviour\TimestampableTrait;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Repository\PartnerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PartnerRepository::class)]
#[UniqueEntity(
    fields: ['email', 'territory', 'isArchive'],
    message: 'L\'e-mail générique existe déjà pour ce territoire. Veuillez saisir un autre e-mail partenaire.',
    errorPath: 'email',
    ignoreNull: true)
]
class Partner implements EntityHistoryInterface
{
    use TimestampableTrait;

    public const string DEFAULT_PARTNER = 'Administrateurs Histologe ALL';
    public const int MAX_LIST_PAGINATION = 50;
    public const array OILHI_TERRITORY_ZIP_ALLOWED = [62]; // Should be replaced by OILHI_CODE_INSEE_ALLOWED
    public const array OILHI_CODE_INSEE_ALLOWED = [62091]; // for testing production

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

    #[ORM\OneToMany(mappedBy: 'partner', targetEntity: User::class, cascade: ['persist'])]
    private Collection $users;

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

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->isArchive = false;
        $this->affectations = new ArrayCollection();
        $this->interventions = new ArrayCollection();
        $this->isIdossActive = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId($id): self
    {
        $this->id = $id;

        return $this;
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

    public function getUsers(): Collection
    {
        return $this->users->filter(function (User $user) {
            if (User::STATUS_ARCHIVE !== $user->getStatut()) {
                return $user;
            }
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

    public function getInsee(): array
    {
        return $this->insee;
    }

    public function setInsee(array $insee): self
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
        $this->email = !empty($email) ? mb_strtolower($email) : null;

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

    public function setType(PartnerType $type): self
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

    public function setCompetence(?array $competence): self
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

    public function setIsEsaboraActive(?bool $isEsaboraActive): self
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
        $emailUsers = $this->users->map(function (User $user) {
            return User::STATUS_ARCHIVE !== $user->getStatut() ? $user->getEmail() : null;
        })->toArray();

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

    public function setIsIdossActive(bool $isIdossActive): self
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

    public function setIdossUrl(?string $idossUrl): self
    {
        $this->idossUrl = $idossUrl;

        return $this;
    }

    public function getIdossToken(): ?string
    {
        return $this->idossToken;
    }

    public function setIdossToken(?string $idossToken): self
    {
        $this->idossToken = $idossToken;

        return $this;
    }

    public function getIdossTokenExpirationDate(): ?\DateTimeInterface
    {
        return $this->idossTokenExpirationDate;
    }

    public function setIdossTokenExpirationDate(?\DateTimeInterface $idossTokenExpirationDate): self
    {
        $this->idossTokenExpirationDate = $idossTokenExpirationDate;

        return $this;
    }

    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }
}
