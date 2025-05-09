<?php

namespace App\Tests;

use App\Entity\Enum\UserStatus;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Entity\UserPartner;

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

        $user = (new User())
            ->setNom('Doe')
            ->setPrenom('John')
            ->setRoles([$role]);

        $userPartner = (new UserPartner())
            ->setUser($user)
            ->setPartner($partner);

        $user->addUserPartner($userPartner);

        return $user;
    }

    public function getUserPronnected(): User
    {
        return (new User())
            ->setEmail('proconnect@signal-logement.fr')
            ->setStatut(UserStatus::ACTIVE)
            ->setRoles([User::ROLE_ADMIN_TERRITORY])
            ->setProConnectUserId('1234');
    }
}
