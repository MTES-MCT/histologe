<?php

namespace App\Event;

use App\Entity\Signalement;
use App\Entity\User;

class SignalementViewedEvent
{
    public const NAME = 'signalement.viewed';

    public function __construct(private Signalement $signalement, private User $user)
    {
    }

    public function getSignalement(): Signalement
    {
        return $this->signalement;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
