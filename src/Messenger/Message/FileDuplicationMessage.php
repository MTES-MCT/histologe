<?php

namespace App\Messenger\Message;

class FileDuplicationMessage
{
    public function __construct(private int $fileId)
    {
    }

    public function getFileId(): int
    {
        return $this->fileId;
    }
}
