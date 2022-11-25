<?php

namespace App\Service\Statistics;

use App\Entity\Territory;
use App\Repository\SignalementRepository;

class SituationStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getFilteredData($statut, $hasCountRefused, $dateStart, $dateEnd, $type, $territory, $etiquettes, $communes)
    {
        $countPerSituations = $this->signalementRepository->countBySituationFiltered($statut, $hasCountRefused, $dateStart, $dateEnd, $type, $territory, $etiquettes, $communes, true);

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
