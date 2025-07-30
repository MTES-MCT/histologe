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
    private ?string $service = null;
    private ?string $action = null;
    private ?string $orderType = null;
    private ?string $reference = null;

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): static
    {
        $this->territory = $territory;

        return $this;
    }

    public function getPartner(): ?Partner
    {
        return $this->partner;
    }

    public function setPartner(?Partner $partner): static
    {
        $this->partner = $partner;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getOrderType(): ?string
    {
        return $this->orderType;
    }

    public function setOrderType(?string $orderType): static
    {
        $this->orderType = $orderType;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getService(): ?string
    {
        return $this->service;
    }

    public function setService(?string $service): static
    {
        $this->service = $service;

        return $this;
    }
}
