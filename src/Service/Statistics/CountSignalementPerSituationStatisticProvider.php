<?php

namespace App\Service\Statistics;

use App\Entity\Territory;
use App\Repository\SignalementRepository;

class CountSignalementPerSituationStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getData(Territory|null $territory, int|null $year)
    {
        $countPerSituations = $this->signalementRepository->countBySituation($territory, $year);
        
        $buffer = [];
        foreach ($countPerSituations as $countPerSituation) {
            if ($countPerSituation['menuLabel']) {
                $buffer[$countPerSituation['menuLabel']] = $countPerSituation['count'];
            }
        }

        return $buffer;
    }
}
