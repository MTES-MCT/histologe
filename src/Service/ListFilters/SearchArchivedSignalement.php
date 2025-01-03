<?php

namespace App\Service\ListFilters;

use App\Entity\Territory;
use App\Service\Behaviour\SearchQueryTrait;

class SearchArchivedSignalement
{
    use SearchQueryTrait;

    private ?string $queryReference = null;
    private ?Territory $territory = null;
    private ?string $orderType = null;

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

    public function getOrderType(): ?string
    {
        return $this->orderType;
    }

    public function setOrderType(?string $orderType): void
    {
        $this->orderType = $orderType;
    }
}
