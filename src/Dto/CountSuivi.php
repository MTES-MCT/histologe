<?php

namespace App\Dto;

class CountSuivi
{
    public function __construct(
        private ?float $average = null,
        private ?int $partner = null,
        private ?int $usager = null,
    ) {
    }

    public function getAverage(): float
    {
        return round($this->average, 1);
    }

    public function getPartner(): int
    {
        return $this->partner;
    }

    public function getUsager(): int
    {
        return $this->usager;
    }
}
