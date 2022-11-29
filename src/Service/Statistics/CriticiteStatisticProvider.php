<?php

namespace App\Service\Statistics;

use App\Dto\BackStatisticsFilters;
use App\Repository\SignalementRepository;

class CriticiteStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getFilteredData(BackStatisticsFilters $backStatisticsFilters)
    {
        $countPerCriticites = $this->signalementRepository->countByCriticiteFiltered($backStatisticsFilters);

        $data = [];
        foreach ($countPerCriticites as $countPerCriticite) {
            if ($countPerCriticite['label']) {
                $data[$countPerCriticite['label']] = $countPerCriticite['count'];
            }
        }

        return $data;
    }
}
