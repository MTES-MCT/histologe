<?php

namespace App\Dto\Request\Signalement;

class QualificationNDERequest
{
    public const RADIO_VALUE_BEFORE_2023 = '1970-01-01';
    public const RADIO_VALUE_AFTER_2023 = '2023-01-02';

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
