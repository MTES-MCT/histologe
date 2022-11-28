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

    public function getData(): array
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

    public function getCountSignalementResoluData(): int
    {
        $countPerMotifsCloture = $this->signalementRepository->countByMotifCloture(null, null, true);
        foreach ($countPerMotifsCloture as $countPerMotifCloture) {
            if ('RESOLU' == $countPerMotifCloture['motifCloture'] && !empty($countPerMotifCloture['count'])) {
                return $countPerMotifCloture['count'];
            }
        }

        return 0;
    }

    public function getCountSignalementData(): int
    {
        return $this->signalementRepository->countAll(null, true, true);
    }

    public function getCountTerritoryData(): int
    {
        return $this->territoryRepository->countAll();
    }

    private function getValidatedData(): string|float
    {
        $total = $this->signalementRepository->countAll(null, true, true);
        if ($total > 0) {
            return round($this->signalementRepository->countValidated(true) / $total * 1000) / 10;
        }

        return '-';
    }

    private function getClotureData(): string|float
    {
        $total = $this->signalementRepository->countAll(null, true, true);
        if ($total > 0) {
            return round($this->signalementRepository->countClosed(true) / $total * 1000) / 10;
        }

        return '-';
    }

    private function getImportedData(): int
    {
        return $this->signalementRepository->countImported();
    }
}
