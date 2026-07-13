<?php

namespace App\Entity;

use App\Entity\Behaviour\TimestampableTrait;
use App\Entity\Enum\PartnerType;
use App\Repository\JobEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobEventRepository::class)]
#[ORM\HasLifecycleCallbacks()]
#[ORM\Index(name: 'idx_job_event_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_job_event_partner_id', columns: ['partner_id'])]
#[ORM\Index(name: 'idx_job_event_service', columns: ['service'])]
#[ORM\Index(name: 'idx_job_event_partner_type', columns: ['partner_type'])]
#[ORM\Index(name: 'idx_job_event_created_at_partner_id', columns: ['created_at', 'partner_id'])]
#[ORM\Index(name: 'idx_job_event_status_created_at', columns: ['status', 'created_at'])]
#[ORM\Index(name: 'idx_job_event_service_action_created_at', columns: ['service', 'action', 'created_at'])]
#[ORM\Index(name: 'idx_job_event_is_operational_error', columns: ['is_operational_error'])]
class JobEvent
{
    use TimestampableTrait;

    public const string STATUS_SUCCESS = 'success';
    public const string STATUS_FAILED = 'failed';
    public const string EXPIRATION_PERIOD_FAILED = '- 6 months';
    public const string EXPIRATION_PERIOD_DEFAULT = '- 1 month';

    // `stream_get_meta_data()`est remonté lorsqu'une ressource est attendue
    // mais qu'une valeur `false` est renvoyée (par exemple par `fopen()` en cas d'échec).
    // Ajout de contrôles manquants
    /* @see UploadHandlerService::toTempFolder */
    /* @see AbstractEsaboraService::preparePiecesJointes */
    public const array OPERATIONAL_ERRORS = [
        '%WS_ERR_MOD_VERIFKEY%',
        '%WS_ERR_DOCUMENT_SIZE%',
        '%WS_ERR_MULT_TIMEOUT%',
        '%WS_ERR_MULT_WSNOTFOUND%',
        '%stream_get_meta_data%',
        '%timeout%',
        '%nginx%',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $signalementId = null;

    #[ORM\Column(nullable: true)]
    private ?int $partnerId = null;

    #[ORM\Column(length: 255)]
    private string $service;

    #[ORM\Column(type: 'string', nullable: true, enumType: PartnerType::class)]
    private ?PartnerType $partnerType = null;

    #[ORM\Column(length: 255)]
    private string $action;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $response = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isOperationalError = false;

    #[ORM\Column(nullable: true)]
    private ?int $attachmentsCount = null;

    #[ORM\Column(nullable: true)]
    private ?int $attachmentsSize = null;

    #[ORM\Column(length: 255)]
    private string $status;

    #[ORM\Column(nullable: true)]
    private ?int $codeStatus = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(?string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(?string $response): static
    {
        $this->response = $response;

        return $this;
    }

    public function isOperationalError(): bool
    {
        return $this->isOperationalError;
    }

    public function setIsOperationalError(bool $isOperationalError): static
    {
        $this->isOperationalError = $isOperationalError;

        return $this;
    }

    public function getSignalementId(): ?int
    {
        return $this->signalementId;
    }

    public function setSignalementId(?int $signalementId): static
    {
        $this->signalementId = $signalementId;

        return $this;
    }

    public function getService(): string
    {
        return $this->service;
    }

    public function setService(?string $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getPartnerId(): ?int
    {
        return $this->partnerId;
    }

    public function setPartnerId(?int $partnerId): static
    {
        $this->partnerId = $partnerId;

        return $this;
    }

    public function getPartnerType(): ?PartnerType
    {
        return $this->partnerType;
    }

    public function setPartnerType(?PartnerType $partnerType): static
    {
        $this->partnerType = $partnerType;

        return $this;
    }

    public function getCodeStatus(): ?int
    {
        return $this->codeStatus;
    }

    public function setCodeStatus(?int $codeStatus): static
    {
        $this->codeStatus = $codeStatus;

        return $this;
    }

    public function getAttachmentsSize(): ?int
    {
        return $this->attachmentsSize;
    }

    public function getAttachmentsCount(): ?int
    {
        return $this->attachmentsCount;
    }

    public function setAttachmentsCount(?int $attachmentsCount): static
    {
        $this->attachmentsCount = $attachmentsCount;

        return $this;
    }

    public function setAttachmentsSize(?int $attachmentsSize): static
    {
        $this->attachmentsSize = $attachmentsSize;

        return $this;
    }
}
