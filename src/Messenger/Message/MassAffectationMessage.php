<?php

namespace App\Messenger\Message;

class MassAffectationMessage
{
    /**
     * @param array<int> $signalementIds
     */
    public function __construct(
        private readonly int $partnerId,
        private readonly array $signalementIds,
        private readonly int $userId,
    ) {
    }

    public function getPartnerId(): int
    {
        return $this->partnerId;
    }

    /**
     * @return array<int>
     */
    public function getSignalementIds(): array
    {
        return $this->signalementIds;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
