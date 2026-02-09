<?php

namespace App\Service\ListFilters;

use App\Entity\Epci;
use App\Entity\Territory;
use App\Service\Behaviour\SearchQueryTrait;

class SearchCommune
{
    use SearchQueryTrait;

    private ?string $queryName = null;
    private ?Territory $territory = null;
    private ?Epci $epci = null;
    private ?string $codePostal = null;
    private ?string $codeInsee = null;
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

    public function getEpci(): ?Epci
    {
        return $this->epci;
    }

    public function setEpci(?Epci $epci): void
    {
        $this->epci = $epci;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(?string $codePostal): void
    {
        $this->codePostal = $codePostal;
    }

    public function getCodeInsee(): ?string
    {
        return $this->codeInsee;
    }

    public function setCodeInsee(?string $codeInsee): void
    {
        $this->codeInsee = $codeInsee;
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
