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
            if ($data[$countPerMotifCloture['motifCloture']->name]) {
                $data[$countPerMotifCloture['motifCloture']->name]['count'] = $countPerMotifCloture['count'];
            }
        }

        return $data;
    }

    private static function initMotifPerValue(): array
    {
        return [
            'TRAVAUX_FAITS_OU_EN_COURS' => [
                'label' => 'Travaux faits ou en cours',
                'color' => '#21AB8E',
                'count' => 0,
            ],
            'RELOGEMENT_OCCUPANT' => [
                'label' => 'Relogement occupant',
                'color' => '#21AB8E',
                'count' => 0,
            ],
            'NON_DECENCE' => [
                'label' => 'Non décence',
                'color' => '#E4794A',
                'count' => 0,
            ],
            'RSD' => [
                'label' => 'RSD',
                'color' => '#A558A0',
                'count' => 0,
            ],
            'INSALUBRITE' => [
                'label' => 'Insalubrité',
                'color' => '#CE0500',
                'count' => 0,
            ],
            'LOGEMENT_DECENT' => [
                'label' => 'Logement décent',
                'color' => '#00A95F',
                'count' => 0,
            ],
            'DEPART_OCCUPANT' => [
                'label' => 'Départ occupant',
                'color' => '#000091',
                'count' => 0,
            ],
            'LOGEMENT_VENDU' => [
                'label' => 'Logement vendu',
                'color' => '#417DC4',
                'count' => 0,
            ],
            'ABANDON_DE_PROCEDURE_ABSENCE_DE_REPONSE' => [
                'label' => 'Abandon de procédure / absence de réponse',
                'color' => '#CACAFB',
                'count' => 0,
            ],
            'PERIL' => [
                'label' => 'Péril',
                'color' => '#CACAFB',
                'count' => 0,
            ],
            'REFUS_DE_VISITE' => [
                'label' => 'Refus de visite',
                'color' => '#CACAFB',
                'count' => 0,
            ],
            'REFUS_DE_TRAVAUX' => [
                'label' => 'Refus de travaux',
                'color' => '#CACAFB',
                'count' => 0,
            ],
            'RESPONSABILITE_DE_L_OCCUPANT' => [
                'label' => "Responsabilité de l'occupant",
                'color' => '#CACAFB',
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
