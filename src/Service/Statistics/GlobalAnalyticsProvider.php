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
        $buffer['count_imported'] = $this->getImportedData();

        return $buffer;
    }

    public function getCountSignalementResoluData()
    {
        $countPerMotifsCloture = $this->signalementRepository->countByMotifCloture(null, null, true);
        foreach ($countPerMotifsCloture as $countPerMotifCloture) {
            if ('RESOLU' == $countPerMotifCloture['motifCloture'] && !empty($countPerMotifCloture['count'])) {
                return $countPerMotifCloture['count'];
            }
        }

        return 0;
    }

    public function getCountSignalementData()
    {
        return $this->signalementRepository->countAll(null, true);
    }

    public function getCountTerritoryData()
    {
        return $this->territoryRepository->countAll();
    }

    private function getValidatedData()
    {
        $total = $this->signalementRepository->countAll(null, true);
        if ($total > 0) {
            return round($this->signalementRepository->countValidated(true) / $total * 1000) / 10;
        }

        return '-';
    }

    private function getClotureData()
    {
        $total = $this->signalementRepository->countAll(null, true);
        if ($total > 0) {
            return round($this->signalementRepository->countClosed(true) / $total * 1000) / 10;
        }

        return '-';
    }

    private function getImportedData()
    {
        return $this->signalementRepository->countImported();
    }
}
