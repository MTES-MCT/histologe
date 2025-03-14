<?php

namespace App\Messenger\Message;

class InactiveUserExportMessage
{
    public function __construct(private int $userId, private string $format)
    {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getFormat(): string
    {
        return $this->format;
    }
}
