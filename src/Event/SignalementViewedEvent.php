<?php

namespace App\Event;

use App\Entity\Signalement;
use App\Entity\User;

class SignalementViewedEvent
{
    public const string NAME = 'signalement.viewed';

    public function __construct(private readonly Signalement $signalement, private readonly User $user)
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
