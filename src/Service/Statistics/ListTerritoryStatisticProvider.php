<?php

namespace App\Service\Statistics;

use App\Entity\Territory;
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
            $data[$territory->getId()] = $territory->getZip().' - '.$territory->getName();
        }

        return $data;
    }
}
