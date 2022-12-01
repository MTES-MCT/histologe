<?php

namespace App\Service\Statistics;

use App\Dto\StatisticsFilters;
use App\Repository\SignalementRepository;

class VisiteStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getFilteredData(StatisticsFilters $statisticsFilters)
    {
        $countPerVisites = $this->signalementRepository->countByVisiteFiltered($statisticsFilters);

        $data = self::initPerVisite();
        foreach ($countPerVisites as $countPerVisite) {
            if ($countPerVisite['visite']) {
                $data[$countPerVisite['visite']]['count'] = $countPerVisite['count'];
            }
        }

        return $data;
    }

    private static function initPerVisite(): array
    {
        return [
            'Oui' => [
                'label' => 'Oui',
                'color' => '#21AB8E',
                'count' => 0,
            ],
            'Non' => [
                'label' => 'Non',
                'color' => '#E4794A',
                'count' => 0,
            ],
        ];
    }
}
