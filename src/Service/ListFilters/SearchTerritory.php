<?php

namespace App\Service\ListFilters;

use App\Service\Behaviour\SearchQueryTrait;

class SearchTerritory
{
    use SearchQueryTrait;

    private ?string $queryName = null;
    private ?bool $isActive = null;
    private ?string $orderType = null;

    public function getQueryName(): ?string
    {
        return $this->queryName;
    }

    public function setQueryName(?string $queryName): void
    {
        $this->queryName = $queryName;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): void
    {
        $this->isActive = $isActive;
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
