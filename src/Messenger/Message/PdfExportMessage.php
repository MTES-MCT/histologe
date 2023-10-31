<?php

namespace App\Messenger\Message;

class PdfExportMessage
{
    private int $signalementId;
    private string $userEmail;

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
}
