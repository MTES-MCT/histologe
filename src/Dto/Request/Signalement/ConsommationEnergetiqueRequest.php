<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Validator\Constraints as Assert;

class ConsommationEnergetiqueRequest implements RequestInterface
{
    public const string RADIO_VALUE_BEFORE_2023 = '1970-01-01';
    public const string RADIO_VALUE_AFTER_2023 = '2023-01-02';

    public function __construct(
        #[Assert\NotBlank(message: 'Merci de préciser la date d\'entrée dans le logement.')]
        private readonly ?string $dateEntree = null,
        #[Assert\Choice(
            choices: [self::RADIO_VALUE_BEFORE_2023, self::RADIO_VALUE_AFTER_2023],
            message: 'Le champ "Date du dernier DPE" est incorrect.'
        )]
        private readonly ?string $dateDernierDPE = null,
        #[Assert\Regex(pattern: '/^\d+(\.\d+)?$/', message: 'La superficie doit être un nombre.')]
        #[Assert\LessThan(value: 10000, message: 'La superficie ne doit pas dépasser 9999 m².')]
        private readonly ?string $superficie = null,
        #[Assert\Regex(pattern: '/^\d+$/', message: 'La consommation énergétique doit être un nombre entier.')]
        private readonly ?string $consommationEnergie = null,
        #[Assert\NotBlank(message: 'Merci de préciser la disponibilité du DPE.')]
        #[Assert\Choice(choices: ['oui', 'non', 'nsp'], message: 'Le champ "DPE disponible" est incorrect.')]
        private readonly ?string $dpe = null,
        // '' : aucune classe sélectionnée (option vide du select)
        #[Assert\Choice(choices: ['', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'nsp'], message: 'Le champ "Classe énergétique" est incorrect.')]
        private readonly ?string $classeEnergetique = null,
    ) {
    }

    public function getDateEntree(): ?string
    {
        return $this->dateEntree;
    }

    public function getSuperficie(): ?float
    {
        return is_numeric($this->superficie) ? (float) $this->superficie : null;
    }

    public function getDateDernierDPE(): ?string
    {
        return $this->dateDernierDPE;
    }

    public function getConsommationEnergie(): ?int
    {
        return is_numeric($this->consommationEnergie) ? (int) $this->consommationEnergie : null;
    }

    public function getDPE(): ?string
    {
        return $this->dpe;
    }

    public function getClasseEnergetique(): ?string
    {
        return '' === $this->classeEnergetique ? null : $this->classeEnergetique;
    }

    /**
     * Détails au format attendu par la qualification NDE (DPE en booléen, consommation en entier).
     *
     * @return array<mixed>
     */
    public function getDetails(): ?array
    {
        return [
            'consommation_energie' => $this->getConsommationEnergie(),
            'DPE' => match ($this->dpe) {
                'oui' => true,
                'non' => false,
                default => null,
            },
            'date_dernier_dpe' => $this->dateDernierDPE,
            'classe_energetique' => $this->getClasseEnergetique(),
        ];
    }
}
