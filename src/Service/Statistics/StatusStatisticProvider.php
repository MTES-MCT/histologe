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

    /**
     * @return array<mixed>
     */
    public function getFilteredData(StatisticsFilters $statisticsFilters): array
    {
        $countPerSituations = $this->signalementRepository->countByStatusFiltered($statisticsFilters);

        return $this->createFullArray($countPerSituations);
    }

    /**
     * @return array<mixed>
     */
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

    /**
     * @param array<mixed> $countPerStatuses
     *
     * @return array<mixed>
     */
    private function createFullArray(array $countPerStatuses): array
    {
        $data = [];
        foreach ($countPerStatuses as $countPerStatus) {
            $item = self::initStatutByValue($countPerStatus);

            if ($item) {
                $data[$countPerStatus['statut']->value] = $item;
            }
        }

        return $data;
    }

    /**
     * @param array<mixed> $statusResult
     *
     * @return bool|array<mixed>
     */
    private static function initStatutByValue(array $statusResult): bool|array
    {
        switch ($statusResult['statut']->value) {
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
