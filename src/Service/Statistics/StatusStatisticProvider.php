<?php

namespace App\Service\Statistics;

use App\Entity\Signalement;
use App\Entity\Territory;
use App\Repository\SignalementRepository;

class StatusStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getFilteredData($statut, $hasCountRefused, $dateStart, $dateEnd, $type, $territory, $etiquettes, $communes)
    {
        $countPerSituations = $this->signalementRepository->countByStatusFiltered($statut, $hasCountRefused, $dateStart, $dateEnd, $type, $territory, $etiquettes, $communes, true);

        return $this->createFullArray($countPerSituations);
    }

    public function getData(Territory|null $territory, int|null $year)
    {
        $countPerStatuses = $this->signalementRepository->countByStatus($territory, $year, true);

        return $this->createFullArray($countPerStatuses);
    }

    private function createFullArray($countPerStatuses)
    {
        $buffer = [];
        foreach ($countPerStatuses as $countPerStatus) {
            $item = self::initStatutByValue($countPerStatus);
            if ($item) {
                $buffer[$countPerStatus['statut']] = $item;
            }
        }

        return $buffer;
    }

    private static function initStatutByValue($statusResult)
    {
        switch ($statusResult['statut']) {
            case Signalement::STATUS_CLOSED:
                return [
                    'label' => 'Fermé',
                    'color' => '#21AB8E',
                    'count' => $statusResult['count'],
                ];
                break;

            case Signalement::STATUS_ACTIVE:
            case Signalement::STATUS_NEED_PARTNER_RESPONSE:
                return [
                    'label' => 'En cours',
                    'color' => '#000091',
                    'count' => $statusResult['count'],
                ];
                break;

            case Signalement::STATUS_NEED_VALIDATION:
                return [
                    'label' => 'Nouveau',
                    'color' => '#E4794A',
                    'count' => $statusResult['count'],
                ];
                break;

            default:
                return false;
                break;
        }
    }
}
