<?php

namespace App\Entity;

use App\Entity\Behaviour\TimestampableTrait;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Repository\PartnerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PartnerRepository::class)]
#[UniqueEntity('email', ignoreNull: true)]
class Partner
{
    use TimestampableTrait;

    public const DEFAULT_PARTNER = 'Administrateurs Histologe ALL';
    public const MAX_LIST_PAGINATION = 50;
    public const TERRITORY_ZIP_ALLOWED = [62]; // Should be replaced by CODE_INSEE_ALLOWED

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
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
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $esaboraUrl = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $esaboraToken = null;

    #[ORM\ManyToOne(targetEntity: Territory::class, inversedBy: 'partners')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Territory $territory = null;

    #[ORM\Column(type: 'string', enumType: PartnerType::class, nullable: true)]
    private ?PartnerType $type = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, length: 255, nullable: true, enumType: Qualification::class)]
    private array $competence = [];

    #[ORM\Column(nullable: true)]
    private ?bool $isEsaboraActive = null;

    #[ORM\OneToMany(mappedBy: 'partner', targetEntity: Intervention::class)]
    private Collection $interventions;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->isArchive = false;
        $this->affectations = new ArrayCollection();
        $this->interventions = new ArrayCollection();
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
            if ($affectation->getSignalement()->getId() === $signalement->getId()) {
                return $signalement;
            }
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
        foreach ($this->users as $user) {
            if ($user->getIsGenerique()) {
                return true;
            }
        }

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

    public function addIntervention(Intervention $intervention): self
    {
        if (!$this->interventions->contains($intervention)) {
            $this->interventions->add($intervention);
            $intervention->setPartner($this);
        }

        return $this;
    }

    public function removeIntervention(Intervention $intervention): self
    {
        if ($this->interventions->removeElement($intervention)) {
            if ($intervention->getPartner() === $this) {
                $intervention->setPartner(null);
            }
        }

        return $this;
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

    public function canSyncWithOilhi(): bool
    {
        return $this->hasCompetence(Qualification::RSD)
            && PartnerType::COMMUNE_SCHS === $this->type
            && \in_array(
                $this->territory->getZip(),
                self::TERRITORY_ZIP_ALLOWED
            );
    }
}
