<?php

namespace App\Service\Statistics;

use App\Repository\Query\Statistics\CountStatisticsQuery;
use App\Repository\Query\Statistics\GlobalStatisticsQuery;
use App\Repository\Query\Statistics\MotifClotureStatisticsQuery;

class GlobalAnalyticsProvider
{
    public function __construct(
        private GlobalStatisticsQuery $globalStatisticsQuery,
        private MotifClotureStatisticsQuery $motifClotureStatisticsQuery,
        private CountStatisticsQuery $countStatisticsQuery,
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        $data = [];
        $data['count_signalement_resolus'] = $this->getCountSignalementResoluData();
        $data['count_signalement'] = $this->getCountSignalementData();
        $data['count_territory'] = $this->getCountTerritoryData();
        $data['percent_validation'] = $this->getValidatedData();
        $data['percent_cloture'] = $this->getClotureData();
        $data['percent_refused'] = $this->getRefusedData();
        $data['count_imported'] = $this->getImportedData();

        return $data;
    }

    public function getCountSignalementResoluData(): int
    {
        return $this->motifClotureStatisticsQuery->countSignalementResolu();
    }

    public function getCountSignalementData(): int
    {
        return $this->globalStatisticsQuery->countSignalements(territory: null, partners: null);
    }

    public function getCountTerritoryData(): int
    {
        return $this->globalStatisticsQuery->countActiveTerritories();
    }

    private function getValidatedData(): string|float
    {
        $total = $this->getCountSignalementData();
        if ($total > 0) {
            return round($this->countStatisticsQuery->countValidated(true) / $total * 1000) / 10;
        }

        return '-';
    }

    private function getClotureData(): string|float
    {
        $total = $this->getCountSignalementData();
        if ($total > 0) {
            return round($this->countStatisticsQuery->countClosed(true) / $total * 1000) / 10;
        }

        return '-';
    }

    private function getRefusedData(): string|float
    {
        $total = $this->getCountSignalementData();
        if ($total > 0) {
            return round($this->countStatisticsQuery->countRefused(true) / $total * 1000) / 10;
        }

        return '-';
    }

    private function getImportedData(): int
    {
        return $this->countStatisticsQuery->countImported();
    }
}
