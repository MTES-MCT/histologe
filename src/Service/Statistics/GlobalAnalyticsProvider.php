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
        $data = [];
        $data['count_signalement_resolus'] = $this->getCountSignalementResoluData();
        $data['count_signalement'] = $this->getCountSignalementData();
        $data['count_territory'] = $this->getCountTerritoryData();
        $data['percent_validation'] = $this->getValidatedData();
        $data['percent_cloture'] = $this->getClotureData();
        $data['count_imported'] = $this->getImportedData();

        return $data;
    }

    public function getCountSignalementResoluData(): int
    {
        $countPerMotifsCloture = $this->signalementRepository->countByMotifCloture(
            territory: null,
            year: null,
            removeImported: true
        );
        foreach ($countPerMotifsCloture as $countPerMotifCloture) {
            if ('RESOLU' == $countPerMotifCloture['motifCloture'] && !empty($countPerMotifCloture['count'])) {
                return $countPerMotifCloture['count'];
            }
        }

        return 0;
    }

    public function getCountSignalementData(): int
    {
        return $this->signalementRepository->countAll(
            territory: null,
            removeImported: true,
            removeArchived: true
        );
    }

    public function getCountTerritoryData(): int
    {
        return $this->territoryRepository->countAll();
    }

    private function getValidatedData(): string|float
    {
        $total = $this->getCountSignalementData();
        if ($total > 0) {
            return round($this->signalementRepository->countValidated(true) / $total * 1000) / 10;
        }

        return '-';
    }

    private function getClotureData(): string|float
    {
        $total = $this->getCountSignalementData();
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
