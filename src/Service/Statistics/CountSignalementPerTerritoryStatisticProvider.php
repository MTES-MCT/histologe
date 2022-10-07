<?php

namespace App\Service\Statistics;

use App\Repository\SignalementRepository;

class CountSignalementPerTerritoryStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getData()
    {
        $buffer = [];

        $countSignalementsByTerritories = $this->signalementRepository->countByTerritory();
        foreach ($countSignalementsByTerritories as $countSignalementsByTerritory) {
            $buffer[$countSignalementsByTerritory['id']] = [
                'name' => $countSignalementsByTerritory['name'],
                'zip' => $countSignalementsByTerritory['zip'],
                'count' => $countSignalementsByTerritory['count'],
            ];
        }

        return $buffer;
    }
}
