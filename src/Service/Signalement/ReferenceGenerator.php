<?php

namespace App\Service\Signalement;

use App\Entity\Territory;
use App\Repository\SignalementRepository;

class ReferenceGenerator
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function generate(Territory $territory): string
    {
        $result = $this->signalementRepository->findLastReferenceByTerritory($territory);
        $todayYear = (new \DateTime())->format('Y');
        if (!empty($result)) {
            list($year, $id) = explode('-', $result['reference']);

            $id = (int) $id;
            ++$id;
            // s'il y a eu des erreurs d'année dans les références, on ne les prolonge pas
            if ($year !== $todayYear) {
                $year = $todayYear;
            }

            return $year.'-'.$id;
        }
        $year = (new \DateTime())->format('Y');

        return $year.'-'. 1;
    }
}
