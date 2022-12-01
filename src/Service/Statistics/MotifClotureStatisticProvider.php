<?php

namespace App\Service\Statistics;

use App\Dto\StatisticsFilters;
use App\Entity\Territory;
use App\Repository\SignalementRepository;

class MotifClotureStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getFilteredData(StatisticsFilters $statisticsFilters): array
    {
        $countPerMotifsCloture = $this->signalementRepository->countByMotifClotureFiltered($statisticsFilters);

        return $this->createFullArray($countPerMotifsCloture);
    }

    public function getData(Territory|null $territory, int|null $year): array
    {
        $countPerMotifsCloture = $this->signalementRepository->countByMotifCloture($territory, $year, true);

        return $this->createFullArray($countPerMotifsCloture);
    }

    private function createFullArray($countPerMotifsCloture): array
    {
        $data = self::initMotifPerValue();
        foreach ($countPerMotifsCloture as $countPerMotifCloture) {
            if ($data[$countPerMotifCloture['motifCloture']]) {
                $data[$countPerMotifCloture['motifCloture']]['count'] = $countPerMotifCloture['count'];
            }
        }

        return $data;
    }

    private static function initMotifPerValue(): array
    {
        return [
            'RESOLU' => [
                'label' => 'Problème résolu',
                'color' => '#21AB8E',
                'count' => 0,
            ],
            'NON_DECENCE' => [
                'label' => 'Non décence',
                'color' => '#E4794A',
                'count' => 0,
            ],
            'INFRACTION RSD' => [
                'label' => 'Infraction RSD',
                'color' => '#A558A0',
                'count' => 0,
            ],
            'INSALUBRITE' => [
                'label' => 'Insalubrité',
                'color' => '#CE0500',
                'count' => 0,
            ],
            'LOGEMENT DECENT' => [
                'label' => 'Logement décent',
                'color' => '#00A95F',
                'count' => 0,
            ],
            'LOCATAIRE PARTI' => [
                'label' => 'Départ occupant',
                'color' => '#000091',
                'count' => 0,
            ],
            'LOGEMENT VENDU' => [
                'label' => 'Logement vendu',
                'color' => '#417DC4',
                'count' => 0,
            ],
            'AUTRE' => [
                'label' => 'Autre',
                'color' => '#CACAFB',
                'count' => 0,
            ],
        ];
    }
}
