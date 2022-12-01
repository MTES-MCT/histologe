<?php

namespace App\Service\Statistics;

use App\Repository\TerritoryRepository;

class ListTerritoryStatisticProvider
{
    public function __construct(private TerritoryRepository $territoryRepository)
    {
    }

    public function getData(): array
    {
        $data = [];
        $territories = $this->territoryRepository->findAllList();
        /** @var Territory $territory */
        foreach ($territories as $territory) {
            $data[$territory->getId()] = $territory->getName();
        }

        return $data;
    }
}
