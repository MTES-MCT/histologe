<?php

namespace App\Service\ListFilters;

use App\Entity\Territory;
use App\Service\Behaviour\SearchQueryTrait;

class SearchBailleur
{
    use SearchQueryTrait;

    private ?string $queryName = null;
    private ?Territory $territory = null;
    private ?string $orderType = null;

    public function getQueryName(): ?string
    {
        return $this->queryName;
    }

    public function setQueryName(?string $queryName): void
    {
        $this->queryName = $queryName;
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
