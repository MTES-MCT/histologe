<?php

namespace App\Event;

use App\Entity\Signalement;
use App\Security\User\SignalementUser;
use Symfony\Contracts\EventDispatcher\Event;

class SuiviViewedEvent extends Event
{
    public const string NAME = 'suivi.viewed';

    public function __construct(private readonly Signalement $signalement, private readonly SignalementUser $user)
    {
    }

    public function getSignalement(): Signalement
    {
        return $this->signalement;
    }

    public function getUser(): SignalementUser
    {
        return $this->user;
    }
}
