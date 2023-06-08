<?php

namespace App\Service\Statistics;

use App\Entity\Partner;
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

    public function getData(?Territory $territory, ?Partner $partner): array
    {
        $data = [];
        $data['count_signalement'] = $this->getCountSignalementData($territory, $partner);
        $data['average_criticite'] = $this->getAverageCriticiteData($territory, $partner);
        $data['average_days_validation'] = $this->getAverageDaysValidationData($territory, $partner);
        $data['average_days_closure'] = $this->getAverageDaysClosureData($territory, $partner);

        $data['count_signalement_archives'] = 0;
        $data['count_signalement_refuses'] = 0;
        $countByStatus = $this->signalementRepository->countByStatus($territory, $partner, null, true);
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

    private function getCountSignalementData(?Territory $territory, ?Partner $partner): int
    {
        return $this->signalementRepository->countAll(
            territory: $territory,
            partner: $partner,
            removeImported: true
        );
    }

    private function getAverageCriticiteData(?Territory $territory, ?Partner $partner): float
    {
        return round($this->signalementRepository->getAverageCriticite($territory, $partner, true) * 10) / 10;
    }

    private function getAverageDaysValidationData(?Territory $territory, ?Partner $partner): float
    {
        return round($this->signalementRepository->getAverageDaysValidation($territory, $partner, true) * 10) / 10;
    }

    private function getAverageDaysClosureData(?Territory $territory, ?Partner $partner): float
    {
        return round($this->signalementRepository->getAverageDaysClosure($territory, $partner, true) * 10) / 10;
    }
}
