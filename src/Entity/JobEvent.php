<?php

namespace App\Entity;

use App\Entity\Behaviour\TimestampableTrait;
use App\Entity\Enum\PartnerType;
use App\Repository\JobEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobEventRepository::class)]
#[ORM\HasLifecycleCallbacks()]
#[ORM\Index(columns: ['created_at'], name: 'idx_job_event_created_at')]
#[ORM\Index(columns: ['partner_id'], name: 'idx_job_event_partner_id')]
#[ORM\Index(columns: ['service'], name: 'idx_job_event_service')]
#[ORM\Index(columns: ['created_at', 'partner_id'], name: 'idx_job_event_created_at_partner_id')]
#[ORM\Index(columns: ['status', 'created_at'], name: 'idx_job_event_status_created_at')]
#[ORM\Index(columns: ['service', 'action', 'created_at'], name: 'idx_job_event_service_action_created_at')]
class JobEvent
{
    use TimestampableTrait;

    public const string STATUS_SUCCESS = 'success';
    public const string STATUS_FAILED = 'failed';
    public const string EXPIRATION_PERIOD = '- 6 months';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $signalementId = null;

    #[ORM\Column(nullable: true)]
    private ?int $partnerId = null;

    #[ORM\Column(length: 255)]
    private ?string $service = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: PartnerType::class)]
    private ?PartnerType $partnerType = null;

    #[ORM\Column(length: 255)]
    private ?string $action = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $response = null;

    #[ORM\Column(nullable: true)]
    private ?int $attachmentsCount = null;

    #[ORM\Column(nullable: true)]
    private ?int $attachmentsSize = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?int $codeStatus = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getStatus(): ?string
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

    public function getSignalementId(): ?int
    {
        return $this->signalementId;
    }

    public function setSignalementId(?int $signalementId): static
    {
        $this->signalementId = $signalementId;

        return $this;
    }

    public function getService(): ?string
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
