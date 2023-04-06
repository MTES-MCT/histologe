<?php

namespace App\Service\Statistics;

use App\Dto\StatisticsFilters;
use App\Repository\SignalementRepository;

class CriticitePercentStatisticProvider
{
    public const CRITICITE_VERY_WEAK = '< 10 %';
    public const CRITICITE_WEAK = 'De 10 à 30 %';
    public const CRITICITE_STRONG = '> 30 %';

    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getFilteredData(StatisticsFilters $statisticsFilters)
    {
        $countPerCriticites = $this->signalementRepository->countByCriticitePercentFiltered($statisticsFilters);

        $data = self::initPerCriticitePercent();

        foreach ($countPerCriticites as $countPerCriticite) {
            if ($countPerCriticite['range']) {
                $data[$countPerCriticite['range']]['count'] = $countPerCriticite['count'];
            }
        }

        return $data;
    }

    private static function initPerCriticitePercent(): array
    {
        return [
            self::CRITICITE_VERY_WEAK => [
                'label' => self::CRITICITE_VERY_WEAK,
                'color' => '#21AB8E',
                'count' => 0,
            ],
            self::CRITICITE_WEAK => [
                'label' => self::CRITICITE_WEAK,
                'color' => '#417DC4',
                'count' => 0,
            ],
            self::CRITICITE_STRONG => [
                'label' => self::CRITICITE_STRONG,
                'color' => '#A558A0',
                'count' => 0,
            ],
        ];
    }
}
