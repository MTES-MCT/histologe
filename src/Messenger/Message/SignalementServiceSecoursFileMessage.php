<?php

namespace App\Messenger\Message;

class SignalementServiceSecoursFileMessage
{
    public function __construct(private readonly int $signalementId)
    {
    }

    public function getSignalementId(): int
    {
        return $this->signalementId;
    }
}
