<?php

namespace App\Service\Statistics;

use App\Repository\TerritoryRepository;

class ListTerritoryStatisticProvider
{
    public function __construct(private TerritoryRepository $territoryRepository)
    {
    }

    public function getData()
    {
        $buffer = [];
        $territories = $this->territoryRepository->findAllList();
        /** @var Territory $territory */
        foreach ($territories as $territory) {
            $buffer[$territory->getId()] = $territory->getName();
        }

        return $buffer;
    }
}
