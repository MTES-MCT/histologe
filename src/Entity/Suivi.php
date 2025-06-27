<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\Enum\SuiviCategory;
use App\Repository\SuiviRepository;
use App\Service\SuiviTransformerService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SuiviRepository::class)]
#[ORM\Index(columns: ['type'], name: 'idx_suivi_type')]
#[ORM\Index(columns: ['created_at'], name: 'idx_suivi_created_at')]
#[ORM\Index(columns: ['signalement_id', 'type', 'created_at'], name: 'idx_suivi_signalement_type_created_at')]
#[ORM\Index(columns: ['context'], name: 'idx_suivi_context')]
class Suivi implements EntityHistoryInterface
{
    public const int TYPE_AUTO = 1;
    public const int TYPE_USAGER = 2;
    public const int TYPE_PARTNER = 3;
    public const int TYPE_TECHNICAL = 4;
    public const int TYPE_USAGER_POST_CLOTURE = 5;
    public const string CONTEXT_NOTIFY_USAGER_ONLY = 'notifyUsagerOnly';
    public const string CONTEXT_INTERVENTION = 'intervention';
    public const string CONTEXT_SCHS = 'schs';
    public const string CONTEXT_SIGNALEMENT_ACCEPTED = 'signalementAccepted';
    public const string CONTEXT_SIGNALEMENT_REFUSED = 'signalementRefused';
    public const string CONTEXT_SIGNALEMENT_CLOSED = 'signalementClosed';

    public const int DEFAULT_PERIOD_INACTIVITY = 30;
    public const int DEFAULT_PERIOD_RELANCE = 45;

    public const string DESCRIPTION_MOTIF_CLOTURE_ALL = 'Le signalement a été cloturé pour tous';
    public const string DESCRIPTION_MOTIF_CLOTURE_PARTNER = 'Le signalement a été cloturé pour';
    public const string DESCRIPTION_SIGNALEMENT_VALIDE = 'Signalement validé';
    public const string DESCRIPTION_DELETED = 'Ce suivi a été supprimé par un administrateur le ';

    public const string ARRET_PROCEDURE = 'arret-procedure';
    public const string POURSUIVRE_PROCEDURE = 'poursuivre-procedure';

    private ?SuiviTransformerService $suiviTransformerService = null;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'suivis')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $createdBy = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $isPublic = null;

    #[ORM\Column(type: 'integer')]
    private ?int $type = null;

    #[ORM\ManyToOne(targetEntity: Signalement::class, inversedBy: 'suivis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Signalement $signalement = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $context = null;

    private bool $sendMail = true;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $deletedBy = null;

    /** @var array<mixed> $originalData */
    #[ORM\Column(nullable: true)]
    private ?array $originalData = null;

    #[ORM\Column]
    private ?bool $isSanitized = null;

    #[ORM\Column(
        type: 'string',
        enumType: SuiviCategory::class,
        options: ['comment' => 'Value possible enum SuiviCategory'],
        nullable: true
    )]
    private ?SuiviCategory $category = null;

    private ?bool $seenByUsager = null;

    /**
     * @var Collection<int, SuiviFile>
     */
    #[ORM\OneToMany(mappedBy: 'suivi', targetEntity: SuiviFile::class, orphanRemoval: true)]
    private Collection $suiviFiles;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isPublic = false;
        $this->isSanitized = true;
        $this->suiviFiles = new ArrayCollection();
    }

    public function setSuiviTransformerService(SuiviTransformerService $suiviTransformerService): void
    {
        $this->suiviTransformerService = $suiviTransformerService;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedByLabel(): ?string
    {
        if (self::TYPE_TECHNICAL === $this->type) {
            return 'Suivi automatique';
        }
        if ($this->getCreatedBy()) {
            if (in_array('ROLE_USAGER', $this->getCreatedBy()->getRoles())) {
                if ($this->getCreatedBy()->getEmail() === $this->getSignalement()->getMailOccupant()) {
                    return 'OCCUPANT : '.ucfirst($this->getCreatedBy()->getNomComplet());
                }

                return 'DECLARANT : '.ucfirst($this->getCreatedBy()->getNomComplet());
            }
            if ($this->getCreatedBy()->getPartnerInTerritoryOrFirstOne($this->getSignalement()->getTerritory())) {
                $partner = $this->getCreatedBy()->getPartnerInTerritoryOrFirstOne($this->getSignalement()->getTerritory());
                if ($partner->getIsArchive()) {
                    return 'Partenaire supprimé';
                }

                return $partner->getNom().' : '.$this->getCreatedBy()->getPrenom().' '.$this->getCreatedBy()->getNom();
            }

            return 'Aucun';
        }
        if ($this->getCreatedAt()->format('Y') >= 2024) {
            return 'Occupant ou déclarant';
        }
        if ($this->getSignalement()->getIsNotOccupant()) {
            return 'DECLARANT : '.strtoupper($this->getSignalement()->getNomDeclarant()).' '.ucfirst($this->getSignalement()->getPrenomDeclarant());
        }

        return 'OCCUPANT : '.strtoupper($this->getSignalement()->getNomOccupant()).' '.ucfirst($this->getSignalement()->getPrenomOccupant());
    }

    public function getDescription(bool $transformHtml = true): ?string
    {
        if (null !== $this->deletedAt) {
            return self::DESCRIPTION_DELETED.' '.$this->deletedAt->format('d/m/Y');
        }

        if (!$transformHtml) {
            $description = $this->description;
        } else {
            $description = str_replace('&lt;br /&gt;', '<br />', $this->description);
        }

        if ($this->suiviTransformerService) {
            return $this->suiviTransformerService->transformDescription($description, $this->getSuiviFiles());
        }

        return $description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getIsPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function getSignalement(): ?Signalement
    {
        return $this->signalement;
    }

    public function setSignalement(?Signalement $signalement): self
    {
        $this->signalement = $signalement;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setContext(?string $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function getSendMail(): bool
    {
        return $this->sendMail;
    }

    public function setSendMail(bool $sendMail): self
    {
        $this->sendMail = $sendMail;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getDeletedBy(): ?User
    {
        return $this->deletedBy;
    }

    public function setDeletedBy(?User $deletedBy): self
    {
        $this->deletedBy = $deletedBy;

        return $this;
    }

    /** @return array<mixed> */
    public function getOriginalData(): ?array
    {
        return $this->originalData;
    }

    /** @param array<mixed> $originalData */
    public function setOriginalData(?array $originalData): static
    {
        $this->originalData = $originalData;

        return $this;
    }

    /** @return array<mixed> */
    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }

    public function getIsSanitized(): ?bool
    {
        return $this->isSanitized;
    }

    public function setIsSanitized(bool $isSanitized): static
    {
        $this->isSanitized = $isSanitized;

        return $this;
    }

    public function getCategory(): ?SuiviCategory
    {
        return $this->category;
    }

    public function setCategory(?SuiviCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function setSeenByUsager(bool $seen): void
    {
        $this->seenByUsager = $seen;
    }

    /**
     * @see templates/back/signalement/view/suivis.html.twig
     */
    public function isSeenByUsager(): ?bool
    {
        return $this->seenByUsager;
    }

    /**
     * @return Collection<int, SuiviFile>
     */
    public function getSuiviFiles(): Collection
    {
        return $this->suiviFiles;
    }

    public function addSuiviFile(SuiviFile $suiviFile): static
    {
        if (!$this->suiviFiles->contains($suiviFile)) {
            $this->suiviFiles->add($suiviFile);
            $suiviFile->setSuivi($this);
        }

        return $this;
    }

    public function removeSuiviFile(SuiviFile $suiviFile): static
    {
        if ($this->suiviFiles->removeElement($suiviFile)) {
            // set the owning side to null (unless already changed)
            if ($suiviFile->getSuivi() === $this) {
                $suiviFile->setSuivi(null);
            }
        }

        return $this;
    }
}
