<?php

namespace App\Messenger\Message;

use App\Entity\Territory;
use App\Entity\User;

class SuiviSummariesMessage
{
    public function __construct(
        private User $user,
        private Territory $territory,
        private int $count,
        private string $prompt,
        private string $querySignalement,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTerritory(): Territory
    {
        return $this->territory;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getPrompt(): string
    {
        return $this->prompt;
    }

    public function getQuerySignalement(): string
    {
        return $this->querySignalement;
    }
}
