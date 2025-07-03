<?php

namespace App\Service\ListFilters;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Service\Behaviour\SearchQueryTrait;

class SearchInterconnexion
{
    use SearchQueryTrait;

    private ?Territory $territory = null;
    private ?Partner $partner = null;
    private ?string $status = null;
    private ?string $orderType = null;
    private ?int $page = 1;

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): void
    {
        $this->territory = $territory;
    }

    public function getPartner(): ?Partner
    {
        return $this->partner;
    }

    public function setPartner(?Partner $partner): void
    {
        $this->partner = $partner;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getOrderType(): ?string
    {
        return $this->orderType;
    }

    public function setOrderType(?string $orderType): void
    {
        $this->orderType = $orderType;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function setPage(?int $page): void
    {
        $this->page = $page;
    }
}
