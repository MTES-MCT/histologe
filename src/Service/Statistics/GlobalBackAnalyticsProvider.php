<?php

namespace App\Service\Statistics;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Territory;
use App\Repository\SignalementRepository;
use Doctrine\Common\Collections\ArrayCollection;

class GlobalBackAnalyticsProvider
{
    public function __construct(
        private SignalementRepository $signalementRepository,
    ) {
    }

    public function getData(?Territory $territory, ArrayCollection $partners): array
    {
        $territories = [];
        if ($territory) {
            $territories[$territory->getId()] = $territory;
        }
        $data = [];
        $data['count_signalement'] = $this->getCountSignalementData($territory, $partners);
        $data['average_criticite'] = $this->getAverageCriticiteData($territory, $partners);
        $data['average_days_validation'] = $this->getAverageDaysValidationData($territory, $partners);
        $data['average_days_closure'] = $this->getAverageDaysClosureData($territory, $partners);

        $data['count_signalement_archives'] = 0;
        $data['count_signalement_refuses'] = 0;
        $countByStatus = $this->signalementRepository->countByStatus($territories, $partners, null, true);
        foreach ($countByStatus as $countByStatusItem) {
            switch ($countByStatusItem['statut']) {
                case SignalementStatus::ARCHIVED->value:
                    $data['count_signalement_archives'] = $countByStatusItem['count'];
                    break;
                case SignalementStatus::REFUSED->value:
                    $data['count_signalement_refuses'] = $countByStatusItem['count'];
                    break;
                default:
                    break;
            }
        }

        return $data;
    }

    private function getCountSignalementData(?Territory $territory, ArrayCollection $partners): int
    {
        return $this->signalementRepository->countAll(
            territory: $territory,
            partners: $partners,
            removeImported: true
        );
    }

    private function getAverageCriticiteData(?Territory $territory, ArrayCollection $partners): float
    {
        return round($this->signalementRepository->getAverageCriticite(
            territory: $territory,
            partners: $partners,
            removeImported: true
        ) * 10) / 10;
    }

    private function getAverageDaysValidationData(?Territory $territory, ArrayCollection $partners): float
    {
        return round($this->signalementRepository->getAverageDaysValidation(
            territory: $territory,
            partners: $partners,
            removeImported: true
        ) * 10) / 10;
    }

    private function getAverageDaysClosureData(?Territory $territory, ArrayCollection $partners): float
    {
        return round($this->signalementRepository->getAverageDaysClosure(
            territory: $territory,
            partners: $partners,
            removeImported: true
        ) * 10) / 10;
    }
}
