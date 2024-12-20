<?php

namespace App\Service\ListFilters;

use App\Service\Behaviour\SearchQueryTrait;

class SearchArchivedUser
{
    use SearchQueryTrait;

    private ?string $queryUser = null;
    private ?string $territory = null;
    private ?string $partner = null;

    public function getQueryUser(): ?string
    {
        return $this->queryUser;
    }

    public function setQueryUser(?string $queryUser): void
    {
        $this->queryUser = $queryUser;
    }

    public function getTerritory(): ?string
    {
        return $this->territory;
    }

    public function setTerritory(?string $territory): void
    {
        $this->territory = $territory;
    }

    public function getPartner(): ?string
    {
        return $this->partner;
    }

    public function setPartner(?string $partner): void
    {
        $this->partner = $partner;
    }
}
