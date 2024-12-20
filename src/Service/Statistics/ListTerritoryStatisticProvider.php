<?php

namespace App\Service\Statistics;

use App\Entity\Territory;
use App\Entity\User;
use App\Repository\TerritoryRepository;

class ListTerritoryStatisticProvider
{
    public function __construct(private TerritoryRepository $territoryRepository)
    {
    }

    public function getData(?User $user = null): array
    {
        $data = [];
        if ($user && !$user->isSuperAdmin()) {
            $territories = $user->getPartnersTerritories();
        } else {
            $territories = $this->territoryRepository->findAllList();
        }
        /** @var Territory $territory */
        foreach ($territories as $territory) {
            $data[$territory->getId()] = $territory->getZip().' - '.$territory->getName();
        }

        return $data;
    }
}
