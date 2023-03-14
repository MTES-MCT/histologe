<?php

namespace App\Service\Statistics;

use App\Dto\StatisticsFilters;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;

class FilteredBackAnalyticsProvider
{
    public function __construct(
        private SignalementRepository $signalementRepository,
        private TerritoryRepository $territoryRepository,
        private MonthStatisticProvider $monthStatisticProvider,
        private PartenaireStatisticProvider $partenaireStatisticProvider,
        private SituationStatisticProvider $situationStatisticProvider,
        private CriticiteStatisticProvider $criticiteStatisticProvider,
        private StatusStatisticProvider $statusStatisticProvider,
        private CriticitePercentStatisticProvider $criticitePercentStatisticProvider,
        private VisiteStatisticProvider $visiteStatisticProvider,
        private MotifClotureStatisticProvider $motifClotureStatisticProvider,
    ) {
    }

    public function getData(StatisticsFilters $statisticsFilters): array
    {
        $data = [];
        $data['count_signalement_filtered'] = $this->getCountSignalementData($statisticsFilters);
        $data['average_criticite_filtered'] = round($this->getAverageCriticiteData($statisticsFilters) * 10) / 10;
        $data['count_signalement_per_month'] = $this->getCountSignalementPerMonth($statisticsFilters);
        $data['count_signalement_per_partenaire'] = $this->getCountSignalementPerPartenaire($statisticsFilters);
        $data['count_signalement_per_situation'] = $this->getCountSignalementPerSituation($statisticsFilters);
        $data['count_signalement_per_criticite'] = $this->getCountSignalementPerCriticite($statisticsFilters);
        $data['count_signalement_per_statut'] = $this->getCountSignalementPerStatut($statisticsFilters);
        $data['count_signalement_per_criticite_percent'] = $this->getCountSignalementPerCriticitePercent($statisticsFilters);
        $data['count_signalement_per_visite'] = $this->getCountSignalementPerVisite($statisticsFilters);
        $data['count_signalement_per_motif_cloture'] = $this->getCountSignalementPerMotifCloture($statisticsFilters);

        return $data;
    }

    private function getCountSignalementData(StatisticsFilters $statisticsFilters): int
    {
        return $this->signalementRepository->countFiltered($statisticsFilters);
    }

    private function getAverageCriticiteData(StatisticsFilters $statisticsFilters): float
    {
        $data = $this->signalementRepository->getAverageCriticiteFiltered($statisticsFilters);
        if (empty($data)) {
            $data = 0;
        }

        return $data;
    }

    private function getCountSignalementPerMonth(StatisticsFilters $statisticsFilters): array
    {
        return $this->monthStatisticProvider->getFilteredData($statisticsFilters);
    }

    private function getCountSignalementPerPartenaire(StatisticsFilters $statisticsFilters): array
    {
        return $this->partenaireStatisticProvider->getFilteredData($statisticsFilters);
    }

    private function getCountSignalementPerSituation(StatisticsFilters $statisticsFilters): array
    {
        return $this->situationStatisticProvider->getFilteredData($statisticsFilters);
    }

    private function getCountSignalementPerCriticite(StatisticsFilters $statisticsFilters): array
    {
        return $this->criticiteStatisticProvider->getFilteredData($statisticsFilters);
    }

    private function getCountSignalementPerStatut(StatisticsFilters $statisticsFilters): array
    {
        return $this->statusStatisticProvider->getFilteredData($statisticsFilters);
    }

    private function getCountSignalementPerCriticitePercent(StatisticsFilters $statisticsFilters): array
    {
        return $this->criticitePercentStatisticProvider->getFilteredData($statisticsFilters);
    }

    private function getCountSignalementPerVisite(StatisticsFilters $statisticsFilters): array
    {
        return $this->visiteStatisticProvider->getFilteredData($statisticsFilters);
    }

    private function getCountSignalementPerMotifCloture(StatisticsFilters $statisticsFilters): array
    {
        return $this->motifClotureStatisticProvider->getFilteredData($statisticsFilters);
    }
}
