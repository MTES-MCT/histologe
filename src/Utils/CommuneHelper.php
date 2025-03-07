<?php

namespace App\Utils;

class CommuneHelper
{
    private const MARSEILLE_ARRONDISSEMENTS = [
        'Marseille 1er Arrondissement',
        'Marseille 2e Arrondissement',
        'Marseille 3e Arrondissement',
        'Marseille 4e Arrondissement',
        'Marseille 5e Arrondissement',
        'Marseille 6e Arrondissement',
        'Marseille 7e Arrondissement',
        'Marseille 8e Arrondissement',
        'Marseille 9e Arrondissement',
        'Marseille 10e Arrondissement',
        'Marseille 11e Arrondissement',
        'Marseille 12e Arrondissement',
        'Marseille 13e Arrondissement',
        'Marseille 14e Arrondissement',
        'Marseille 15e Arrondissement',
        'Marseille 16e Arrondissement',
    ];

    private const LYON_ARRONDISSEMENTS = [
        'Lyon 1er Arrondissement',
        'Lyon 2e Arrondissement',
        'Lyon 3e Arrondissement',
        'Lyon 4e Arrondissement',
        'Lyon 5e Arrondissement',
        'Lyon 6e Arrondissement',
        'Lyon 7e Arrondissement',
        'Lyon 8e Arrondissement',
        'Lyon 9e Arrondissement',
    ];

    private const PARIS_ARRONDISSEMENTS = [
        'Paris 1er Arrondissement',
        'Paris 2e Arrondissement',
        'Paris 3e Arrondissement',
        'Paris 4e Arrondissement',
        'Paris 5e Arrondissement',
        'Paris 6e Arrondissement',
        'Paris 7e Arrondissement',
        'Paris 8e Arrondissement',
        'Paris 9e Arrondissement',
        'Paris 10e Arrondissement',
        'Paris 11e Arrondissement',
        'Paris 12e Arrondissement',
        'Paris 13e Arrondissement',
        'Paris 14e Arrondissement',
        'Paris 15e Arrondissement',
        'Paris 16e Arrondissement',
        'Paris 17e Arrondissement',
        'Paris 18e Arrondissement',
        'Paris 19e Arrondissement',
        'Paris 20e Arrondissement',
    ];

    public const COMMUNES_ARRONDISSEMENTS = [
        'Marseille' => self::MARSEILLE_ARRONDISSEMENTS,
        'Lyon' => self::LYON_ARRONDISSEMENTS,
        'Paris' => self::PARIS_ARRONDISSEMENTS,
    ];

    public static function getCommuneFromArrondissement(?string $commune): ?string
    {
        foreach (self::COMMUNES_ARRONDISSEMENTS as $communeName => $communeArrondissements) {
            if (in_array($commune, $communeArrondissements)) {
                return $communeName;
            }
        }

        return $commune;
    }
}
