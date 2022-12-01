<?php

namespace App\Service\Statistics;

use App\Dto\StatisticsFilters;
use App\Repository\SignalementRepository;

class CriticiteStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getFilteredData(StatisticsFilters $statisticsFilters)
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
