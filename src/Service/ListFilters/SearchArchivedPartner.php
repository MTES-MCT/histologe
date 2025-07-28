<?php

namespace App\Service\ListFilters;

use App\Entity\Territory;
use App\Service\Behaviour\SearchQueryTrait;

class SearchArchivedPartner
{
    use SearchQueryTrait;

    private ?string $queryArchivedPartner = null;
    private ?Territory $territory = null;
    private ?string $orderType = null;

    public function getQueryArchivedPartner(): ?string
    {
        return $this->queryArchivedPartner;
    }

    public function setQueryArchivedPartner(?string $queryArchivedPartner): void
    {
        $this->queryArchivedPartner = $queryArchivedPartner;
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
