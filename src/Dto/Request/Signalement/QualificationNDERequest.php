<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Validator\Constraints as Assert;

class QualificationNDERequest implements RequestInterface
{
    public const string RADIO_VALUE_BEFORE_2023 = '1970-01-01';
    public const string RADIO_VALUE_AFTER_2023 = '2023-01-02';

    public function __construct(
        private ?string $dateEntree = null,
        private ?string $dateDernierDPE = null,
        #[Assert\LessThan(value: 10000, message: 'La superficie ne doit pas dépasser 9999 m².')]
        private ?float $superficie = null,
        private ?int $consommationEnergie = null,
        private ?bool $dpe = null,
        private ?string $classeEnergetique = null,
    ) {
    }

    public function getDateEntree(): ?string
    {
        return $this->dateEntree;
    }

    public function getSuperficie(): ?float
    {
        return $this->superficie;
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

    public function getClasseEnergetique(): ?string
    {
        return $this->classeEnergetique;
    }

    /**
     * @return array<mixed>
     */
    public function getDetails(): ?array
    {
        return [
            'consommation_energie' => $this->consommationEnergie,
            'DPE' => $this->dpe,
            'date_dernier_dpe' => $this->dateDernierDPE,
            'classe_energetique' => $this->classeEnergetique,
        ];
    }
}
