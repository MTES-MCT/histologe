<?php

namespace App\Service\Metabase;

use App\Entity\Territory;
use App\Entity\User;

class DashboardFilter
{
    public function __construct(private readonly User $user, private ?Territory $territory = null)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): void
    {
        $this->territory = $territory;
    }
}
