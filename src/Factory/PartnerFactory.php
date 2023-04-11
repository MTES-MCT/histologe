<?php

namespace App\Factory;

use App\Entity\Enum\PartnerType;
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
        PartnerType $type = null,
        string $insee = null): Partner
    {
        $partner = (new Partner())
            ->setTerritory($territory)
            ->setNom($name)
            ->setEmail($email)
            ->setType($type)
            ->setIsArchive(false);

        if (!empty($insee)) {
            $partner->setInsee(array_map('trim', explode(',', $insee)));
        }

        return $partner;
    }
}
