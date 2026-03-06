<?php

namespace App\Factory;

use App\Entity\Signalement;
use App\Entity\TiersInvitation;

class TiersInvitationFactory
{
    public function __construct()
    {
    }

    public function createInstanceFrom(
        Signalement $signalement,
        string $lastName,
        string $firstName,
        string $email,
        ?string $telephone = null,
    ): TiersInvitation {
        return (new TiersInvitation())
             ->setSignalement($signalement)
             ->setLastname($lastName)
             ->setFirstname($firstName)
             ->setEmail($email)
             ->setTelephone($telephone);
    }
}
