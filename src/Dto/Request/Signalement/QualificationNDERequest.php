<?php

namespace App\Dto\Request\Signalement;

class QualificationNDERequest
{
    public function __construct(
        private ?string $dateEntree = null,
        private ?string $dateDernierBail = null,
        private ?string $dateDernierDPE = null,
        private ?int $superficie = null,
        private ?int $consommationEnergie = null,
        private ?bool $dpe = null,
    ) {
    }

    public function getDateEntree(): ?string
    {
        return $this->dateEntree;
    }

    public function getSuperficie(): ?int
    {
        return $this->superficie;
    }

    public function getDateDernierBail(): ?string
    {
        return $this->dateDernierBail;
    }

    public function getDateDernierDPE(): ?string
    {
        return $this->dateDernierDPE;
    }

    public function getConsommationEnergie(): ?int
    {
        return $this->consommationEnergie;
    }

    public function getDPE(): ?bool
    {
        return $this->dpe;
    }

    public function getDetails(): ?array
    {
        return [
            'consommation_energie' => $this->consommationEnergie,
            'DPE' => $this->dpe,
            'date_dernier_dpe' => $this->dateDernierDPE,
        ];
    }
}
