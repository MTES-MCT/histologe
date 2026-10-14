<?php

namespace App\Service\ListFilters;

use App\Entity\Enum\ServiceSecoursCategory;
use App\Entity\Territory;
use App\Service\Behaviour\SearchQueryTrait;

class SearchServiceSecoursRoute
{
    use SearchQueryTrait;

    private ?Territory $territory = null;
    private ?ServiceSecoursCategory $category = null;
    private ?string $queryName = null;
    private ?string $orderType = null;

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): void
    {
        $this->territory = $territory;
    }

    public function getCategory(): ?ServiceSecoursCategory
    {
        return $this->category;
    }

    public function setCategory(?ServiceSecoursCategory $category): void
    {
        $this->category = $category;
    }

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

    public function setOrderType(?string $orderType): self
    {
        $this->orderType = $orderType;

        return $this;
    }
}
