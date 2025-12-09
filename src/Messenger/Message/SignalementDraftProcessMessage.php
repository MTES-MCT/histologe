<?php

namespace App\Messenger\Message;

class SignalementDraftProcessMessage
{
    public function __construct(private ?int $signalementDraftId, private ?int $signalementId)
    {
    }

    public function getSignalementDraftId(): ?int
    {
        return $this->signalementDraftId;
    }

    public function getSignalementId(): ?int
    {
        return $this->signalementId;
    }
}
