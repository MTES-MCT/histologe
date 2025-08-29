<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\DocumentType;
use App\Entity\Enum\HistoryEntryEvent;
use App\Repository\FileRepository;
use App\Service\ImageManipulationHandler;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FileRepository::class)]
#[ORM\Index(columns: ['is_standalone'], name: 'idx_is_standalone')]
class File implements EntityHistoryInterface
{
    public const STANDALONE_FILES = [
        '1 - Demande de transmission d\'une copie d\'un DPE' => '1_Demande_de_transmission_d_une_copie_d_un_DPE.docx',
        '2 - Information au bailleur - Mise en conformité' => '2_Information_au_bailleur_Mise_en_conformite.docx',
        '3 - Mise en demeure' => '3_Mise_en_demeure.docx',
        '4 - Invitation à contacter l\'ADIL' => '4_Invitation_a_contacter_l_ADIL.docx',
        '5 - Engagement du bailleur à réaliser des travaux' => '5_Engagement_du_bailleur_a_realiser_des_travaux.docx',
        '6 - Saisine de la Commission départementale de conciliation' => '6_Saisine_de_la_Commission_departementale_de_conciliation.docx',
    ];

    /** @var string[] */
    public const array DOCUMENT_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.oasis.opendocument.text',
        'application/msword',
        'text/plain',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/octet-stream',
        'message/rfc822',
        'application/vnd.ms-outlook',
    ];
    /** @var string[] */
    public const array DOCUMENT_EXTENSION = [
        'jpeg',
        'jpg',
        'png',
        'gif',
        'pdf',
        'docx',
        'odt',
        'doc',
        'txt',
        'xls',
        'xlsx',
        'eml',
        'msg',
    ];
    /** @var string[] */
    public const array RESIZABLE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
    ];
    /** @var string[] */
    public const array RESIZABLE_EXTENSION = [
        'jpeg',
        'jpg',
        'png',
        'gif',
    ];
    /** @var string[] */
    public const array IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
    ];
    /** @var string[] */
    public const array IMAGE_EXTENSION = [
        'jpeg',
        'jpg',
        'png',
        'gif',
        'pdf',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'files')]
    private ?User $uploadedBy = null;

    #[ORM\ManyToOne(inversedBy: 'files')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Signalement $signalement = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $extension = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'files')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Intervention $intervention = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?string $size = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: DocumentType::class)]
    private ?DocumentType $documentType = null;

    #[ORM\Column(nullable: true)]
    private ?string $desordreSlug = null;

    #[ORM\Column]
    private ?bool $isVariantsGenerated = null;

    #[ORM\Column(type: 'text', length: 250, nullable: true)]
    #[Assert\Length(max: 250)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $isWaitingSuivi = null;

    #[ORM\Column]
    private ?bool $isTemp = null;

    /** @var array<mixed> $synchroData */
    #[ORM\Column(nullable: true)]
    private ?array $synchroData = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $scannedAt = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $uuid = null;

    #[ORM\Column]
    private ?bool $isOriginalDeleted = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isSuspicious = null;

    #[ORM\Column()]
    private ?bool $isStandalone = null;

    #[ORM\ManyToOne(targetEntity: Territory::class, inversedBy: 'files')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Territory $territory = null;

    public function __construct()
    {
        $this->uuid = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
        $this->isVariantsGenerated = false;
        $this->isOriginalDeleted = false;
        $this->isWaitingSuivi = false;
        $this->isTemp = false;
        $this->isStandalone = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): self
    {
        $this->uploadedBy = $uploadedBy;

        return $this;
    }

    public function isUsagerFile(): ?bool
    {
        return !$this->isPartnerFile();
    }

    public function isPartnerFile(): ?bool
    {
        return null !== $this->uploadedBy
        && ($this->uploadedBy->isSuperAdmin()
        || $this->uploadedBy->isTerritoryAdmin()
        || $this->uploadedBy->isPartnerAdmin()
        || $this->uploadedBy->isUserPartner()
        || $this->uploadedBy->isApiUser());
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

    public function getFilename(?string $variantName = null): ?string
    {
        if ($this->isVariantsGenerated && $variantName) {
            $variantNames = ImageManipulationHandler::getVariantNames($this->filename);
            if (isset($variantNames[$variantName])) {
                return $variantNames[$variantName];
            }
        }

        return $this->filename;
    }

    public function setFilename(?string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getIntervention(): ?Intervention
    {
        return $this->intervention;
    }

    public function setIntervention(?Intervention $intervention): self
    {
        $this->intervention = $intervention;

        return $this;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(?string $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getDocumentType(): ?DocumentType
    {
        return $this->documentType;
    }

    public function setDocumentType(?DocumentType $documentType): self
    {
        $this->documentType = $documentType;

        return $this;
    }

    public function getDesordreSlug(): ?string
    {
        return $this->desordreSlug;
    }

    public function setDesordreSlug(?string $desordreSlug): self
    {
        $this->desordreSlug = $desordreSlug;

        return $this;
    }

    public function isIsVariantsGenerated(): ?bool
    {
        return $this->isVariantsGenerated;
    }

    public function setIsVariantsGenerated(bool $isVariantsGenerated): self
    {
        $this->isVariantsGenerated = $isVariantsGenerated;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $description = null !== $description ? trim($description) : null;
        $this->description = $description ?: null;

        return $this;
    }

    public function isIsWaitingSuivi(): ?bool
    {
        return $this->isWaitingSuivi;
    }

    public function setIsWaitingSuivi(bool $isWaitingSuivi): self
    {
        $this->isWaitingSuivi = $isWaitingSuivi;

        return $this;
    }

    public function isTypeImage(): bool
    {
        return in_array($this->getExtension(), self::RESIZABLE_EXTENSION);
    }

    public function isTypeDocument(): bool
    {
        return !$this->isTypeImage();
    }

    public function isSituationImage(): bool
    {
        return $this->isTypeImage() && \array_key_exists($this->documentType->value, DocumentType::getOrderedSituationList()) && null === $this->intervention;
    }

    public function isProcedureImage(): bool
    {
        return $this->isTypeImage() && \array_key_exists($this->documentType->value, DocumentType::getOrderedProcedureList()) && null === $this->intervention;
    }

    public function isSituationDocument(): bool
    {
        return $this->isTypeDocument() && \array_key_exists($this->documentType->value, DocumentType::getOrderedSituationList()) && null === $this->intervention;
    }

    public function isProcedureDocument(): bool
    {
        return $this->isTypeDocument() && \array_key_exists($this->documentType->value, DocumentType::getOrderedProcedureList()) && null === $this->intervention;
    }

    public function isTemp(): ?bool
    {
        return $this->isTemp;
    }

    public function setIsTemp(?bool $isTemp): self
    {
        $this->isTemp = $isTemp;

        return $this;
    }

    /** @return array<mixed> */
    public function getSynchroData(?string $key): ?array
    {
        if ($key) {
            return $this->synchroData[$key] ?? null;
        }

        return $this->synchroData;
    }

    /** @param array<mixed> $data */
    public function setSynchroData(array $data, string $key): self
    {
        $this->synchroData[$key] = $data;

        return $this;
    }

    public function getScannedAt(): ?\DateTimeImmutable
    {
        return $this->scannedAt;
    }

    public function setScannedAt(?\DateTimeImmutable $scannedAt): static
    {
        $this->scannedAt = $scannedAt;

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getIsOriginalDeleted(): ?bool
    {
        return $this->isOriginalDeleted;
    }

    public function setIsOriginalDeleted(bool $isOriginalDeleted): static
    {
        $this->isOriginalDeleted = $isOriginalDeleted;

        return $this;
    }

    public function getIsSuspicious(): ?bool
    {
        return $this->isSuspicious;
    }

    public function setIsSuspicious(?bool $isSuspicious): self
    {
        $this->isSuspicious = $isSuspicious;

        return $this;
    }

    public function getIsStandalone(): ?bool
    {
        return $this->isStandalone;
    }

    public function setIsStandalone(?bool $isStandalone): static
    {
        $this->isStandalone = $isStandalone;

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

    /** @return array<HistoryEntryEvent> */
    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }
}
