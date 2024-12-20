<?php

namespace App\Service;

use App\Entity\Territory;
use App\Service\Behaviour\SearchQueryTrait;

class SearchArchivedSignalement
{
    use SearchQueryTrait;

    private ?string $queryReference = null;
    private ?Territory $territory = null;

    public function getQueryReference(): ?string
    {
        return $this->queryReference;
    }

    public function setQueryReference(?string $queryReference): void
    {
        $this->queryReference = $queryReference;
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
