<?php

namespace App\Service\Statistics;

use App\Entity\Signalement;
use App\Entity\Territory;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;

class GlobalBackAnalyticsProvider
{
    public function __construct(
        private SignalementRepository $signalementRepository,
        private TerritoryRepository $territoryRepository
        ) {
    }

    public function getData(?Territory $territory): array
    {
        $data = [];
        $data['count_signalement'] = $this->getCountSignalementData($territory);
        $data['average_criticite'] = $this->getAverageCriticiteData($territory);
        $data['average_days_validation'] = $this->getAverageDaysValidationData($territory);
        $data['average_days_closure'] = $this->getAverageDaysClosureData($territory);

        $data['count_signalement_archives'] = 0;
        $data['count_signalement_refuses'] = 0;
        $countByStatus = $this->signalementRepository->countByStatus($territory, null, true);
        foreach ($countByStatus as $countByStatusItem) {
            switch ($countByStatusItem['statut']) {
                case Signalement::STATUS_ARCHIVED:
                    $data['count_signalement_archives'] = $countByStatusItem['count'];
                    break;
                case Signalement::STATUS_REFUSED:
                    $data['count_signalement_refuses'] = $countByStatusItem['count'];
                    break;
                default:
                    break;
            }
        }

        return $data;
    }

    private function getCountSignalementData(?Territory $territory): int
    {
        return $this->signalementRepository->countAll($territory, true);
    }

    private function getAverageCriticiteData(?Territory $territory): float
    {
        return round($this->signalementRepository->getAverageCriticite($territory, true) * 10) / 10;
    }

    private function getAverageDaysValidationData(?Territory $territory): float
    {
        return round($this->signalementRepository->getAverageDaysValidation($territory, true) * 10) / 10;
    }

    private function getAverageDaysClosureData(?Territory $territory): float
    {
        return round($this->signalementRepository->getAverageDaysClosure($territory, true) * 10) / 10;
    }
}
