<?php

namespace App\Service\Statistics;

use App\Entity\Territory;
use App\Repository\SignalementRepository;

class DesordresCategoriesStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getData(Territory|null $territory, int|null $year): array
    {
        $countPerDesordresCategories = $this->signalementRepository->countCritereByZone(
            $territory,
            $year,
            true
        );

        return $this->createFullArray($countPerDesordresCategories);
    }

    private function createFullArray(array $dataZone): array
    {
        $data = self::initDesordresCategoriesPerValue();

        $data['BATIMENT']['count'] = $dataZone['critere_batiment_count'] + $dataZone['desordrecritere_batiment_count'];
        $data['LOGEMENT']['count'] = $dataZone['critere_logement_count'] + $dataZone['desordrecritere_logement_count'];

        return $data;
    }

    private static function initDesordresCategoriesPerValue(): array
    {
        return [
            'BATIMENT' => [
                'label' => 'Bâtiment',
                'color' => '#2F4077',
                'count' => 0,
            ],
            'LOGEMENT' => [
                'label' => 'Logement',
                'color' => '#447049',
                'count' => 0,
            ],
        ];
    }
}
