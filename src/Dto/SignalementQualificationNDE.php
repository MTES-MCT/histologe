<?php

namespace App\Dto;

use DateTimeImmutable;

class SignalementQualificationNDE
{
    public function __construct(
        private ?string $dateEntree,
        private ?DateTimeImmutable $dateDernierBail,
        private ?DateTimeImmutable $dateDernierDPE,
        private ?int $superficie,
        private ?int $consommationEnergie,
        private ?bool $dpe,
    ) {
    }

    public function getDateEntree(): ?string
    {
        return $this->dateEntree;
    }

    public function getDateDernierBail(): ?DateTimeImmutable
    {
        return $this->dateDernierBail;
    }

    public function getDateDernierDPE(): ?DateTimeImmutable
    {
        return $this->dateDernierDPE;
    }

    public function getSuperficie(): ?int
    {
        return $this->superficie;
    }

    public function getConsommationEnergie(): ?int
    {
        return $this->consommationEnergie;
    }

    public function getDPE(): ?bool
    {
        return $this->dpe;
    }
}
