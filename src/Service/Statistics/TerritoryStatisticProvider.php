<?php

namespace App\Service\Statistics;

use App\Repository\SignalementRepository;

class TerritoryStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getData(): array
    {
        $data = [];

        $countSignalementsByTerritories = $this->signalementRepository->countByTerritory(true);
        foreach ($countSignalementsByTerritories as $countSignalementsByTerritory) {
            $data[$countSignalementsByTerritory['id']] = [
                'name' => $countSignalementsByTerritory['name'],
                'zip' => $countSignalementsByTerritory['zip'],
                'count' => $countSignalementsByTerritory['count'],
            ];
        }

        return $data;
    }
}
