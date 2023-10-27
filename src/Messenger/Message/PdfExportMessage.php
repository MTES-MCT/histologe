<?php

namespace App\Messenger\Message;

class PdfExportMessage
{
    private int $signalementId;
    private string $userEmail;
    private ?array $options;
    private ?array $criticites;

    public function getSignalementId(): int
    {
        return $this->signalementId;
    }

    public function setSignalementId(int $signalementId): self
    {
        $this->signalementId = $signalementId;

        return $this;
    }

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    public function setUserEmail(string $userEmail): self
    {
        $this->userEmail = $userEmail;

        return $this;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function setOptions(?array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function getCriticites(): ?array
    {
        return $this->criticites;
    }

    public function setCriticites(?array $criticites): self
    {
        $this->criticites = $criticites;

        return $this;
    }
}
