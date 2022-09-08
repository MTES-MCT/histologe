<?php

namespace App\Factory;

use App\Entity\Partner;
use App\Entity\Territory;

class PartnerFactory
{
    public function __construct()
    {
    }

    public function createInstanceFrom(
        Territory $territory,
        string $name = null,
        string $email = null,
        bool $isCommune = false,
        array $insee = [])
    {
        return (new Partner())
            ->setTerritory($territory)
            ->setNom($name)
            ->setEmail(mb_strtolower($email))
            ->setIsCommune($isCommune)
            ->setInsee($insee)
            ->setIsArchive(false);
    }
}
