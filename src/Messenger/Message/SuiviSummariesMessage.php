<?php

namespace App\Messenger\Message;

readonly class SuiviSummariesMessage
{
    public function __construct(
        private int $userId,
        private int $territoryId,
        private int $count,
        private string $prompt,
        private string $model,
        private string $querySignalement,
    ) {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getTerritoryId(): int
    {
        return $this->territoryId;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getPrompt(): string
    {
        return $this->prompt;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getQuerySignalement(): string
    {
        return $this->querySignalement;
    }
}
