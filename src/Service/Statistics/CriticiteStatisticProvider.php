<?php

namespace App\Service\Statistics;

use App\Dto\StatisticsFilters;
use App\Repository\Query\Statistics\CriticiteStatisticsQuery;

class CriticiteStatisticProvider
{
    public function __construct(private CriticiteStatisticsQuery $criticiteStatisticsQuery)
    {
    }

    /** @return array<string, string> */
    public function getFilteredData(StatisticsFilters $statisticsFilters): array
    {
        $countPerCriticites = $this->criticiteStatisticsQuery->countByCriticiteFiltered($statisticsFilters);

        $data = [];
        foreach ($countPerCriticites as $countPerCriticite) {
            if ($countPerCriticite['label']) {
                $data[$countPerCriticite['label']] = $countPerCriticite['count'];
            }
        }

        return $data;
    }
}
