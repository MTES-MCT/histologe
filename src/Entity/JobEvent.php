<?php

namespace App\Entity;

use App\Entity\Behaviour\TimestampableTrait;
use App\Entity\Enum\PartnerType;
use App\Repository\JobEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobEventRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class JobEvent
{
    use TimestampableTrait;

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

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

    public function setAction(?string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(?string $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function getSignalementId(): ?int
    {
        return $this->signalementId;
    }

    public function setSignalementId(?int $signalementId): self
    {
        $this->signalementId = $signalementId;

        return $this;
    }

    public function getService(): ?string
    {
        return $this->service;
    }

    public function setService(?string $service): self
    {
        $this->service = $service;

        return $this;
    }

    public function getPartnerId(): ?int
    {
        return $this->partnerId;
    }

    public function setPartnerId(?int $partnerId): self
    {
        $this->partnerId = $partnerId;

        return $this;
    }

    public function getPartnerType(): ?PartnerType
    {
        return $this->partnerType;
    }

    public function setPartnerType(?PartnerType $partnerType): self
    {
        $this->partnerType = $partnerType;

        return $this;
    }

    public function getCodeStatus(): ?int
    {
        return $this->codeStatus;
    }

    public function setCodeStatus(?int $codeStatus): self
    {
        $this->codeStatus = $codeStatus;

        return $this;
    }
}
