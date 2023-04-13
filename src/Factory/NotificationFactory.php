<?php

namespace App\Factory;

use App\Entity\Notification;
use App\Entity\Suivi;
use App\Entity\User;

class NotificationFactory
{
    public function createInstanceFrom(User $user, Suivi $suivi): Notification
    {
        return (new Notification())
            ->setUser($user)
            ->setSuivi($suivi)
            ->setSignalement($suivi->getSignalement())
            ->setType(Notification::TYPE_SUIVI);
    }
}
