<?php

namespace App\Service;

use App\Service\Behaviour\SearchQueryTrait;

class SearchArchivedPartner
{
    use SearchQueryTrait;

    private ?string $queryArchivedPartner = null;
    private ?string $territory = null;

    public function getQueryArchivedPartner(): ?string
    {
        return $this->queryArchivedPartner;
    }

    public function setQueryArchivedPartner(?string $queryArchivedPartner): void
    {
        $this->queryArchivedPartner = $queryArchivedPartner;
    }

    public function getTerritory(): ?string
    {
        return $this->territory;
    }

    public function setTerritory(?string $territory): void
    {
        $this->territory = $territory;
    }
}
