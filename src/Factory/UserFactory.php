<?php

namespace App\Factory;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;

class UserFactory
{
    public function __construct()
    {
    }

    public function createInstanceFrom(
        string $roleLabel,
        ?Partner $partner,
        ?Territory $territory,
        ?string $firstname,
        ?string $lastname,
        ?string $email): User
    {
        return (new User())
            ->setRoles([User::ROLES[$roleLabel]])
            ->setPartner($partner)
            ->setTerritory($territory)
            ->setPrenom($firstname)
            ->setNom($lastname)
            ->setEmail($email)
            ->setIsGenerique(false)
            ->setStatut(User::STATUS_INACTIVE)
            ->setIsMailingActive(true);
    }
}
