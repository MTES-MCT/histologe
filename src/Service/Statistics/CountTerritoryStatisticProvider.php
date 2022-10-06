<?php

namespace App\Service\Statistics;

use App\Repository\TerritoryRepository;

class CountTerritoryStatisticProvider
{
    public function __construct(private TerritoryRepository $territoryRepository)
    {
    }

    public function getData()
    {
        return $this->territoryRepository->countAll();
    }
}
