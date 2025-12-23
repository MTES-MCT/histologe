<?php

namespace App\Messenger\Message;

class GenerateFileZipMessage
{
    /**
     * @param array<int> $fileIds
     */
    public function __construct(
        private readonly int $userId,
        private readonly int $signalementId,
        private array $fileIds = [],
    ) {
        $this->fileIds = array_values(array_unique(array_map('intval', $this->fileIds)));
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getSignalementId(): int
    {
        return $this->signalementId;
    }

    public function getFileIds(): array
    {
        return $this->fileIds;
    }
}
