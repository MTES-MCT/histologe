<?php

namespace App\Service\Statistics;

use App\Repository\SignalementRepository;

class PercentSignalementValidatedStatisticProvider
{
    public function __construct(private SignalementRepository $signalementRepository)
    {
    }

    public function getData()
    {
        $total = $this->signalementRepository->countAll();
        if ($total > 0) {
            return round($this->signalementRepository->countValidated() / $total * 1000) / 10;
        }

        return '-';
    }
}
