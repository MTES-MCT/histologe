<?php

namespace App\Factory;

use App\Entity\Commune;
use App\Entity\Territory;

class CommuneFactory
{
    public function __construct()
    {
    }

    public function createInstanceFrom(
        Territory $territory,
        string $nom = null,
        string $codePostal = null,
        string $codeInsee = null,
        bool $isZonePermisLouer = false
    ) {
        return (new Commune())
            ->setTerritory($territory)
            ->setNom($nom)
            ->setCodePostal($codePostal)
            ->setCodeInsee($codeInsee)
            ->setIsZonePermisLouer($isZonePermisLouer);
    }
}
