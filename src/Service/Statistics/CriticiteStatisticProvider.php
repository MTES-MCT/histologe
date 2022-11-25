<?php

namespace App\Service\Statistics;

use App\Repository\SignalementRepository;

class CriticiteStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getFilteredData($statut, $hasCountRefused, $dateStart, $dateEnd, $type, $territory, $etiquettes, $communes)
    {
        $countPerCriticites = $this->signalementRepository->countByCriticiteFiltered($statut, $hasCountRefused, $dateStart, $dateEnd, $type, $territory, $etiquettes, $communes, true);

        $buffer = [];
        foreach ($countPerCriticites as $countPerCriticite) {
            if ($countPerCriticite['label']) {
                $buffer[$countPerCriticite['label']] = $countPerCriticite['count'];
            }
        }

        return $buffer;
    }
}
