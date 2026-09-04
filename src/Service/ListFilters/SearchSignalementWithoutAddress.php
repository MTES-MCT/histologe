<?php

namespace App\Service\ListFilters;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Territory;
use App\Service\Behaviour\SearchQueryTrait;

class SearchSignalementWithoutAddress
{
    use SearchQueryTrait;

    private ?Territory $territory = null;
    private ?SignalementStatus $statut = null;
    private ?bool $isImported = null;
    private ?string $orderType = null;

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): void
    {
        $this->territory = $territory;
    }

    public function getStatut(): ?SignalementStatus
    {
        return $this->statut;
    }

    public function setStatut(?SignalementStatus $statut): void
    {
        $this->statut = $statut;
    }

    public function getIsImported(): ?bool
    {
        return $this->isImported;
    }

    public function setIsImported(?bool $isImported): void
    {
        $this->isImported = $isImported;
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
