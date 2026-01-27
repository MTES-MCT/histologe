<?php

namespace App\Service\ListFilters;

use App\Service\Behaviour\SearchQueryTrait;

class SearchServiceSecoursRoute
{
    use SearchQueryTrait;

    private ?string $queryName = null;
    private ?string $orderType = null;

    public function getQueryName(): ?string
    {
        return $this->queryName;
    }

    public function setQueryName(?string $queryName): void
    {
        $this->queryName = $queryName;
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
