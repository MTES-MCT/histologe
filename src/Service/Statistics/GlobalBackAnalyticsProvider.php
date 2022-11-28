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

    public function getData(?Territory $territory)
    {
        $buffer = [];
        $buffer['count_signalement'] = $this->getCountSignalementData($territory);
        $buffer['average_criticite'] = $this->getAverageCriticiteData($territory);
        $buffer['average_days_validation'] = $this->getAverageDaysValidationData($territory);
        $buffer['average_days_closure'] = $this->getAverageDaysClosureData($territory);

        $buffer['count_signalement_archives'] = 0;
        $buffer['count_signalement_refuses'] = 0;
        $countByStatus = $this->signalementRepository->countByStatus($territory, null, true);
        foreach ($countByStatus as $countByStatusItem) {
            switch ($countByStatusItem['statut']) {
                case Signalement::STATUS_ARCHIVED:
                    $buffer['count_signalement_archives'] = $countByStatusItem['count'];
                    break;
                case Signalement::STATUS_REFUSED:
                    $buffer['count_signalement_refuses'] = $countByStatusItem['count'];
                    break;
                default:
                    break;
            }
        }

        return $buffer;
    }

    private function getCountSignalementData(?Territory $territory)
    {
        return $this->signalementRepository->countAll($territory, true);
    }

    private function getAverageCriticiteData(?Territory $territory)
    {
        return round($this->signalementRepository->getAverageCriticite($territory, true) * 10) / 10;
    }

    private function getAverageDaysValidationData(?Territory $territory)
    {
        return round($this->signalementRepository->getAverageDaysValidation($territory, true) * 10) / 10;
    }

    private function getAverageDaysClosureData(?Territory $territory)
    {
        return round($this->signalementRepository->getAverageDaysClosure($territory, true) * 10) / 10;
    }

    private function getCountSignalementsRefusesData(?Territory $territory)
    {
        return $this->signalementRepository->countAll($territory, true);
    }

    private function getCountSignalementArchivesData(?Territory $territory)
    {
        return $this->signalementRepository->countAll($territory, true);
    }
}
