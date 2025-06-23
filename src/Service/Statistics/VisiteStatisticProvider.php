<?php

namespace App\Service\Statistics;

use App\Dto\StatisticsFilters;
use App\Repository\SignalementRepository;

class VisiteStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    /**
     * @return array<mixed>
     */
    public function getFilteredData(StatisticsFilters $statisticsFilters): array
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

    /**
     * @return array<mixed>
     */
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
