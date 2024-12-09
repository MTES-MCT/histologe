<?php

namespace App\Messenger\Message;

class SignalementUpdateFromAddressMessage
{
    public function __construct(private ?int $signalementId)
    {
    }

    public function getSignalementId(): ?int
    {
        return $this->signalementId;
    }
}
