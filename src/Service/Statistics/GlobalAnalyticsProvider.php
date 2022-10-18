<?php

namespace App\Service\Statistics;

use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;

class GlobalAnalyticsProvider
{
    public function __construct(
        private SignalementRepository $signalementRepository,
        private TerritoryRepository $territoryRepository
        ) {
    }

    public function getData()
    {
        $buffer = [];
        $buffer['count_signalement_resolus'] = $this->getCountSignalementResoluData();
        $buffer['count_signalement'] = $this->getCountSignalementData();
        $buffer['count_territory'] = $this->getCountTerritoryData();
        $buffer['percent_validation'] = $this->getValidatedData();
        $buffer['percent_cloture'] = $this->getClotureData();

        return $buffer;
    }

    public function getCountSignalementResoluData()
    {
        $countPerMotifsCloture = $this->signalementRepository->countByMotifCloture(null, null);
        foreach ($countPerMotifsCloture as $countPerMotifCloture) {
            if ('RESOLU' == $countPerMotifCloture['motifCloture'] && !empty($countPerMotifCloture['count'])) {
                return $countPerMotifCloture['count'];
            }
        }

        return 0;
    }

    public function getCountSignalementData()
    {
        return $this->signalementRepository->countAll();
    }

    public function getCountTerritoryData()
    {
        return $this->territoryRepository->countAll();
    }

    private function getValidatedData()
    {
        $total = $this->signalementRepository->countAll();
        if ($total > 0) {
            return round($this->signalementRepository->countValidated() / $total * 1000) / 10;
        }

        return '-';
    }

    private function getClotureData()
    {
        $total = $this->signalementRepository->countAll();
        if ($total > 0) {
            return round($this->signalementRepository->countClosed() / $total * 1000) / 10;
        }

        return '-';
    }
}
