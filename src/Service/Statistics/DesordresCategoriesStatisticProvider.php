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

    private function createFullArray($countPerDesordresCategories): array
    {
        $data = self::initDesordresCategoriesPerValue();

        $data[0]['count'] = $countPerDesordresCategories['critere_batiment_count'] + $countPerDesordresCategories['desordrecritere_batiment_count'];
        $data[1]['count'] = $countPerDesordresCategories['critere_logement_count'] + $countPerDesordresCategories['desordrecritere_logement_count'];

        return $data;
    }

    private static function initDesordresCategoriesPerValue(): array
    {
        return [
            0 => [
                'label' => 'Bâtiment',
                'color' => '#2F4077',
                'count' => 0,
            ],
            1 => [
                'label' => 'Logement',
                'color' => '#447049',
                'count' => 0,
            ],
        ];
    }
}
