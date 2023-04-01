<?php

namespace App\Tests;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;

trait UserHelper
{
    public function getUserFromRole(string $role): User
    {
        $territory = (new Territory())
            ->setName('Ain')
            ->setZip('01')
            ->setBbox([])
            ->setIsActive(true);

        $partner = (new Partner())
            ->setNom('Partner')
            ->setTerritory($territory);

        return (new User())
            ->setNom('Doe')
            ->setPrenom('John')
            ->setRoles([$role])
            ->setPartner($partner)->setTerritory($territory);
    }
}
