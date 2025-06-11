<?php

namespace App\Service\Interconnection;

use App\Entity\Enum\PartnerType;

readonly class JobEventMetaData
{
    /**
     * @param ?array<mixed>
     */
    public function __construct(
        private string $service,
        private string $action,
        private ?array $payload = null,
        private ?int $signalementId = null,
        private ?int $partnerId = null,
        private ?PartnerType $partnerType = null,
    ) {
    }

    public function getService(): string
    {
        return $this->service;
    }

    /**
     * @return ?array<mixed>
     */
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getSignalementId(): ?int
    {
        return $this->signalementId;
    }

    public function getPartnerId(): ?int
    {
        return $this->partnerId;
    }

    public function getPartnerType(): ?PartnerType
    {
        return $this->partnerType;
    }
}
