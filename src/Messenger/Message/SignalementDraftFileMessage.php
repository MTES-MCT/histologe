<?php

namespace App\Messenger\Message;

class SignalementDraftFileMessage
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
