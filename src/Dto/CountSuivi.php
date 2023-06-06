<?php

namespace App\Dto;

class CountSuivi
{
    public function __construct(
        private ?float $average = null,
        private ?int $partner = null,
        private ?int $usager = null,
        private ?int $signalementNewSuivi = null,
        private ?int $signalementNoSuivi = null,
        private ?int $noSuiviAfter3Relances = null,
    ) {
    }

    public function getAverage(): ?float
    {
        return round($this->average, 1);
    }

    public function getPartner(): ?int
    {
        return $this->partner;
    }

    public function getUsager(): ?int
    {
        return $this->usager;
    }

    public function getSignalementNewSuivi(): ?int
    {
        return $this->signalementNewSuivi;
    }

    public function getSignalementNoSuivi(): ?int
    {
        return $this->signalementNoSuivi;
    }

    public function getNoSuiviAfter3Relances(): ?int
    {
        return $this->noSuiviAfter3Relances;
    }
}
