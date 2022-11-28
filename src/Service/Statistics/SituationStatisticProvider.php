<?php

namespace App\Service\Statistics;

use App\Entity\Territory;
use App\Repository\SignalementRepository;

class SituationStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getFilteredData(FilteredBackAnalyticsProvider $filters)
    {
        $countPerSituations = $this->signalementRepository->countBySituationFiltered($filters);

        return $this->createFullArray($countPerSituations);
    }

    public function getData(Territory|null $territory, int|null $year)
    {
        $countPerSituations = $this->signalementRepository->countBySituation($territory, $year, true);

        return $this->createFullArray($countPerSituations);
    }

    private function createFullArray($countPerSituations)
    {
        $buffer = [];
        foreach ($countPerSituations as $countPerSituation) {
            if ($countPerSituation['menuLabel']) {
                $buffer[$countPerSituation['menuLabel']] = $countPerSituation['count'];
            }
        }

        return $buffer;
    }
}
