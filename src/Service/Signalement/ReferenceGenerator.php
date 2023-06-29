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

        if (!empty($result)) {
            list($year, $id) = explode('-', $result['reference']);

            $id = (int) $id;
            ++$id;

            return $year.'-'.$id;
        }
        $year = (new \DateTime())->format('Y');

        return $year.'-'. 1;
    }
}
