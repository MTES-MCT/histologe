<?php

namespace App\Dto;

class CountSuivi
{
    public function __construct(
        private float $average = 0,
        private int $partner = 0,
        private int $usager = 0,
    ) {
    }

    public function getAverage(): float
    {
        return $this->average;
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
