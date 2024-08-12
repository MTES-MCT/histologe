<?php

namespace App\Service\Statistics;

use App\Dto\StatisticsFilters;
use App\Entity\Territory;
use App\Repository\SignalementRepository;

class SituationStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getFilteredData(StatisticsFilters $statisticsFilters): array
    {
        $countPerSituations = $this->signalementRepository->countBySituationFiltered($statisticsFilters);

        return $this->createFullArray($countPerSituations);
    }

    public function getData(?Territory $territory, ?int $year): array
    {
        $countPerSituations = $this->signalementRepository->countBySituation($territory, $year, true);

        return $this->createFullArray($countPerSituations);
    }

    private function createFullArray($countPerSituations): array
    {
        $data = [];
        foreach ($countPerSituations as $countPerSituation) {
            if ($countPerSituation['menuLabel']) {
                $data[$countPerSituation['menuLabel']] = $countPerSituation['count'];
            }
        }

        return $data;
    }
}
