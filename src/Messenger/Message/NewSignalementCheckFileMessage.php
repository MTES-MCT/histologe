<?php

namespace App\Messenger\Message;

class NewSignalementCheckFileMessage
{
    public function __construct(private ?int $signalementId)
    {
    }

    public function getSignalementId(): ?int
    {
        return $this->signalementId;
    }
}
