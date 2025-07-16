<?php

namespace App\Service\ListFilters;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Territory;
use App\Service\Behaviour\SearchQueryTrait;

class SearchAffectationWithoutSubscription
{
    use SearchQueryTrait;
    private ?Territory $territory = null;
    private ?SignalementStatus $signalementStatus = null;
    private ?string $orderType = null;

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): void
    {
        $this->territory = $territory;
    }

    public function getSignalementStatus(): ?SignalementStatus
    {
        return $this->signalementStatus;
    }

    public function setSignalementStatus(?SignalementStatus $signalementStatus): void
    {
        $this->signalementStatus = $signalementStatus;
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
