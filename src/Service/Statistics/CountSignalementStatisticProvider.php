<?php

namespace App\Service\Statistics;

use App\Repository\SignalementRepository;

class CountSignalementStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function get()
    {
        return $this->signalementRepository->countAll();
    }
}
