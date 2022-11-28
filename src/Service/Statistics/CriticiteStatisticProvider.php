<?php

namespace App\Service\Statistics;

use App\Repository\SignalementRepository;

class CriticiteStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getFilteredData(FilteredBackAnalyticsProvider $filters)
    {
        $countPerCriticites = $this->signalementRepository->countByCriticiteFiltered($filters);

        $buffer = [];
        foreach ($countPerCriticites as $countPerCriticite) {
            if ($countPerCriticite['label']) {
                $buffer[$countPerCriticite['label']] = $countPerCriticite['count'];
            }
        }

        return $buffer;
    }
}
