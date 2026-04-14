<?php

namespace App\Service\Statistics;

use App\Repository\Query\Statistics\CountStatisticsQuery;
use App\Service\Signalement\ZipcodeProvider;

class TerritoryStatisticProvider
{
    public function __construct(
        private readonly CountStatisticsQuery $countStatisticsQuery, )
    {
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        $data = [];

        $countSignalementsByTerritories = $this->countStatisticsQuery->countByTerritory(true);
        $rhoneId = null;
        $metropoleDeLyonId = null;
        foreach ($countSignalementsByTerritories as $countSignalementsByTerritory) {
            if (ZipcodeProvider::METROPOLE_LYON_CODE_DEPARTMENT_69A === $countSignalementsByTerritory['zip']) {
                $metropoleDeLyonId = $countSignalementsByTerritory['id'];
            }
            if (ZipcodeProvider::RHONE_CODE_DEPARTMENT_69 === $countSignalementsByTerritory['zip']) {
                $rhoneId = $countSignalementsByTerritory['id'];
            }

            $data[$countSignalementsByTerritory['id']] = [
                'name' => $countSignalementsByTerritory['name'],
                'zip' => $countSignalementsByTerritory['zip'],
                'count' => $countSignalementsByTerritory['count'],
            ];
        }
        $data[$rhoneId]['count'] += $data[$metropoleDeLyonId]['count'];
        unset($data[$metropoleDeLyonId]);

        return $data;
    }
}
