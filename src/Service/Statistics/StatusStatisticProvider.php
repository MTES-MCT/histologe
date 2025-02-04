<?php

namespace App\Service\Statistics;

use App\Dto\StatisticsFilters;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Territory;
use App\Repository\SignalementRepository;

class StatusStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getFilteredData(StatisticsFilters $statisticsFilters): array
    {
        $countPerSituations = $this->signalementRepository->countByStatusFiltered($statisticsFilters);

        return $this->createFullArray($countPerSituations);
    }

    public function getData(?Territory $territory, ?int $year): array
    {
        $territories = [];
        if ($territory) {
            $territories[$territory->getId()] = $territory;
        }
        $countPerStatuses = $this->signalementRepository->countByStatus(
            territories: $territories,
            partners: null,
            year: $year,
            removeImported: true
        );

        return $this->createFullArray($countPerStatuses);
    }

    private function createFullArray($countPerStatuses): array
    {
        $data = [];
        foreach ($countPerStatuses as $countPerStatus) {
            $item = self::initStatutByValue($countPerStatus);
            if ($item) {
                $data[$countPerStatus['statut']] = $item;
            }
        }

        return $data;
    }

    private static function initStatutByValue($statusResult): bool|array
    {
        switch ($statusResult['statut']) {
            case SignalementStatus::REFUSED->value:
                return [
                    'label' => 'Refusé',
                    'color' => '#CE0500',
                    'count' => $statusResult['count'],
                ];

            case SignalementStatus::CLOSED->value:
                return [
                    'label' => 'Fermé',
                    'color' => '#21AB8E',
                    'count' => $statusResult['count'],
                ];

            case SignalementStatus::ACTIVE->value:
                return [
                    'label' => 'En cours',
                    'color' => '#000091',
                    'count' => $statusResult['count'],
                ];

            case SignalementStatus::NEED_VALIDATION->value:
                return [
                    'label' => 'Nouveau',
                    'color' => '#E4794A',
                    'count' => $statusResult['count'],
                ];

            default:
                return false;
        }
    }
}
