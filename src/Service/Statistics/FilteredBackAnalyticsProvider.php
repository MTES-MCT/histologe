<?php

namespace App\Service\Statistics;

use App\Dto\BackStatisticsFilters;
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

    public function getData(BackStatisticsFilters $backStatisticsFilters): array
    {
        $data = [];
        $data['count_signalement_filtered'] = $this->getCountSignalementData($backStatisticsFilters);
        $data['average_criticite_filtered'] = round($this->getAverageCriticiteData($backStatisticsFilters) * 10) / 10;
        $data['count_signalement_per_month'] = $this->getCountSignalementPerMonth($backStatisticsFilters);
        $data['count_signalement_per_partenaire'] = $this->getCountSignalementPerPartenaire($backStatisticsFilters);
        $data['count_signalement_per_situation'] = $this->getCountSignalementPerSituation($backStatisticsFilters);
        $data['count_signalement_per_criticite'] = $this->getCountSignalementPerCriticite($backStatisticsFilters);
        $data['count_signalement_per_statut'] = $this->getCountSignalementPerStatut($backStatisticsFilters);
        $data['count_signalement_per_criticite_percent'] = $this->getCountSignalementPerCriticitePercent($backStatisticsFilters);
        $data['count_signalement_per_visite'] = $this->getCountSignalementPerVisite($backStatisticsFilters);
        $data['count_signalement_per_motif_cloture'] = $this->getCountSignalementPerMotifCloture($backStatisticsFilters);

        return $data;
    }

    private function getCountSignalementData(BackStatisticsFilters $backStatisticsFilters): int
    {
        return $this->signalementRepository->countFiltered($backStatisticsFilters);
    }

    private function getAverageCriticiteData(BackStatisticsFilters $backStatisticsFilters): float
    {
        $data = $this->signalementRepository->getAverageCriticiteFiltered($backStatisticsFilters);
        if (empty($data)) {
            $data = 0;
        }

        return $data;
    }

    private function getCountSignalementPerMonth(BackStatisticsFilters $backStatisticsFilters): array
    {
        return $this->monthStatisticProvider->getFilteredData($backStatisticsFilters);
    }

    private function getCountSignalementPerPartenaire(BackStatisticsFilters $backStatisticsFilters): array
    {
        return $this->partenaireStatisticProvider->getFilteredData($backStatisticsFilters);
    }

    private function getCountSignalementPerSituation(BackStatisticsFilters $backStatisticsFilters): array
    {
        return $this->situationStatisticProvider->getFilteredData($backStatisticsFilters);
    }

    private function getCountSignalementPerCriticite(BackStatisticsFilters $backStatisticsFilters): array
    {
        return $this->criticiteStatisticProvider->getFilteredData($backStatisticsFilters);
    }

    private function getCountSignalementPerStatut(BackStatisticsFilters $backStatisticsFilters): array
    {
        return $this->statusStatisticProvider->getFilteredData($backStatisticsFilters);
    }

    private function getCountSignalementPerCriticitePercent(BackStatisticsFilters $backStatisticsFilters): array
    {
        return $this->criticitePercentStatisticProvider->getFilteredData($backStatisticsFilters);
    }

    private function getCountSignalementPerVisite(BackStatisticsFilters $backStatisticsFilters): array
    {
        return $this->visiteStatisticProvider->getFilteredData($backStatisticsFilters);
    }

    private function getCountSignalementPerMotifCloture(BackStatisticsFilters $backStatisticsFilters): array
    {
        return $this->motifClotureStatisticProvider->getFilteredData($backStatisticsFilters);
    }
}
