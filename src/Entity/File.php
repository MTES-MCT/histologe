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
class File implements EntityHistoryInterface
{
    public const FILE_TYPE_DOCUMENT = 'document';
    public const FILE_TYPE_PHOTO = 'photo';
    public const INPUT_NAME_PHOTOS = 'photos';
    public const INPUT_NAME_DOCUMENTS = 'documents';
    public const DOCUMENT_MIME_TYPES = [
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
    public const DOCUMENT_EXTENSION = [
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
    public const IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
    ];
    public const IMAGE_EXTENSION = [
        'jpeg',
        'jpg',
        'png',
        'gif',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'files')]
    private ?User $uploadedBy = null;

    #[ORM\ManyToOne(inversedBy: 'files')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Signalement $signalement = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 32, options: ['comment' => 'Value possible photo or document'])]
    private ?string $fileType = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'files')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Intervention $intervention = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?string $size = null;

    #[ORM\Column(type: 'string', enumType: DocumentType::class, nullable: true)]
    private ?DocumentType $documentType = null;

    #[ORM\Column(nullable: true)]
    private ?string $desordreSlug = null;

    #[ORM\Column]
    private ?bool $isVariantsGenerated = null;

    #[ORM\Column(type: 'text', nullable: true, length: 250)]
    #[Assert\Length(max: 250)]
    private ?string $description;

    #[ORM\Column]
    private ?bool $isWaitingSuivi = null;

    #[ORM\Column]
    private ?bool $isTemp = null;

    #[ORM\Column(nullable: true)]
    private ?array $synchroData = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $scannedAt = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $uuid = null;

    public function __construct()
    {
        $this->uuid = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
        $this->isVariantsGenerated = false;
        $this->isWaitingSuivi = false;
        $this->isTemp = false;
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
        || $this->uploadedBy->isUserPartner());
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

    public function getFileType(): ?string
    {
        return $this->fileType;
    }

    public function setFileType(?string $fileType): self
    {
        $this->fileType = $fileType;

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

    public function setDocumentType(DocumentType $documentType): self
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
        if (
            !$this->getSignalement()
            || !$this->getDocumentType()
            || DocumentType::PHOTO_SITUATION !== $this->getDocumentType()
        ) {
            $this->desordreSlug = null;

            return $this;
        }
        if (
            \in_array($desordreSlug, $this->getSignalement()->getDesordreCritereSlugs())
            || \in_array($desordreSlug, $this->getSignalement()->getDesordrePrecisionSlugs())
            || \in_array($desordreSlug, $this->getSignalement()->getDesordreCategorieSlugs())
        ) {
            $this->desordreSlug = $desordreSlug;
        } else {
            $this->desordreSlug = null;
        }

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
        $this->description = $description;

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

    public function isSituationPhoto(): bool
    {
        return $this->fileType === $this::FILE_TYPE_PHOTO
        && \array_key_exists($this->documentType->value, DocumentType::getOrderedSituationList())
        && null === $this->intervention;
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

    public function getSynchroData(?string $key): ?array
    {
        if ($key) {
            return $this->synchroData[$key] ?? null;
        }

        return $this->synchroData;
    }

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

    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }
}
