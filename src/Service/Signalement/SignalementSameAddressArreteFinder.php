<?php

namespace App\Service\Signalement;

use App\Entity\Arrete;
use App\Entity\Signalement;
use App\Repository\ArreteRepository;

class SignalementSameAddressArreteFinder
{
    public function __construct(
        private readonly ArreteRepository $arreteRepository,
    ) {
    }

    /**
     * @return Arrete[]
     */
    public function find(Signalement $signalement): array
    {
        return $this->arreteRepository->findByAddress($signalement->getAddress());
    }
}
