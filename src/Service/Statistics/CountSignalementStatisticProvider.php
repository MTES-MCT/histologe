<?php

namespace App\Service\Statistics;

use App\Repository\SignalementRepository;

class CountSignalementStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getData()
    {
        return $this->signalementRepository->countAll();
    }
}
