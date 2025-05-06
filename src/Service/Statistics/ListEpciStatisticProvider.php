<?php

namespace App\Service\Statistics;

use App\Entity\Epci;
use App\Entity\Territory;
use App\Repository\EpciRepository;

class ListEpciStatisticProvider
{
    public function __construct(private readonly EpciRepository $epciRepository)
    {
    }

    /** @return array<string, string> */
    public function getData(?Territory $territory): array
    {
        $data = [];
        if (null !== $territory) {
            $epciList = $this->epciRepository->findAllByTerritory($territory);
            /** @var Epci $epciItem */
            foreach ($epciList as $epciItem) {
                $data[$epciItem->getId()] = $epciItem->getNom();
            }
        }

        return $data;
    }
}
