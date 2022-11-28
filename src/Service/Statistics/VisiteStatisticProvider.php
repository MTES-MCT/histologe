<?php

namespace App\Service\Statistics;

use App\Repository\SignalementRepository;

class VisiteStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getFilteredData($statut, $hasCountRefused, $dateStart, $dateEnd, $type, $territory, $etiquettes, $communes)
    {
        $countPerVisites = $this->signalementRepository->countByVisiteFiltered($statut, $hasCountRefused, $dateStart, $dateEnd, $type, $territory, $etiquettes, $communes, true);

        $buffer = self::initPerVisite();
        foreach ($countPerVisites as $countPerVisite) {
            if ($countPerVisite['visite']) {
                $buffer[$countPerVisite['visite']]['count'] = $countPerVisite['count'];
            }
        }

        return $buffer;
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
