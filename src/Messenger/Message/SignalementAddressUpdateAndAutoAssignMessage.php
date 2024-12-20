<?php

namespace App\Messenger\Message;

class SignalementAddressUpdateAndAutoAssignMessage
{
    public function __construct(private ?int $signalementId)
    {
    }

    public function getSignalementId(): ?int
    {
        return $this->signalementId;
    }
}
