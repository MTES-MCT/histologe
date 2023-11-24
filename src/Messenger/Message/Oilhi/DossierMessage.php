<?php

namespace App\Messenger\Message\Oilhi;

final class DossierMessage
{
    private ?string $signalementUrl = null;
    private ?int $signalementId = null;
    private ?int $partnerId = null;

    public function getSignalementUrl(): ?string
    {
        return $this->signalementUrl;
    }

    public function setSignalementUrl(?string $signalementUrl): self
    {
        $this->signalementUrl = $signalementUrl;

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

    public function getPartnerId(): ?int
    {
        return $this->partnerId;
    }

    public function setPartnerId(?int $partnerId): self
    {
        $this->partnerId = $partnerId;

        return $this;
    }
}
