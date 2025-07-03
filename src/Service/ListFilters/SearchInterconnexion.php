<?php

namespace App\Service\ListFilters;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Service\Behaviour\SearchQueryTrait;

class SearchInterconnexion
{
    use SearchQueryTrait;

    private Territory|int|null $territory = null;
    private Partner|int|null $partner = null;
    private ?string $status = null;
    private ?string $orderType = null;
    private ?int $page = 1;

    public function getTerritory(): Territory|int|null
    {
        return $this->territory;
    }

    public function setTerritory(Territory|int|null $territory): void
    {
        $this->territory = $territory;
    }

    public function getTerritoryId(): ?int
    {
        if ($this->territory instanceof Territory) {
            return $this->territory->getId();
        }

        return is_int($this->territory) ? $this->territory : null;
    }

    public function getPartner(): Partner|int|null
    {
        return $this->partner;
    }

    public function setPartner(Partner|int|null $partner): void
    {
        $this->partner = $partner;
    }

    public function getPartnerId(): ?int
    {
        if ($this->partner instanceof Partner) {
            return $this->partner->getId();
        }

        return is_int($this->partner) ? $this->partner : null;
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
