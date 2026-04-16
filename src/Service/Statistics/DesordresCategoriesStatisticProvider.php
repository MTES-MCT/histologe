<?php

namespace App\Service\Statistics;

use App\Entity\Territory;
use App\Repository\Query\Statistics\CountStatisticsQuery;

class DesordresCategoriesStatisticProvider
{
    public function __construct(
        private readonly CountStatisticsQuery $countStatisticsQuery, )
    {
    }

    /**
     * @return array<mixed>
     */
    public function getData(?Territory $territory, ?int $year): array
    {
        $countPerDesordresCategories = $this->countStatisticsQuery->countCritereByZone(
            $territory,
            $year
        );

        return $this->createFullArray($countPerDesordresCategories);
    }

    /**
     * @param array<mixed> $dataZone
     *
     * @return array<mixed>
     */
    private function createFullArray(array $dataZone): array
    {
        $data = self::initDesordresCategoriesPerValue();

        $data['BATIMENT']['count'] = $dataZone['critere_batiment_count'] + $dataZone['desordrecritere_batiment_count'];
        $data['LOGEMENT']['count'] = $dataZone['critere_logement_count'] + $dataZone['desordrecritere_logement_count'];

        return $data;
    }

    /**
     * @return array<mixed>
     */
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
