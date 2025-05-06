<?php

namespace App\Service\Statistics;

use App\Dto\StatisticsFilters;
use App\Repository\SignalementRepository;

class CriticiteStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    /** @return array<string, string> */
    public function getFilteredData(StatisticsFilters $statisticsFilters): array
    {
        $countPerCriticites = $this->signalementRepository->countByCriticiteFiltered($statisticsFilters);

        $data = [];
        foreach ($countPerCriticites as $countPerCriticite) {
            if ($countPerCriticite['label']) {
                $data[$countPerCriticite['label']] = $countPerCriticite['count'];
            }
        }

        return $data;
    }
}
