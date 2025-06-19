<?php

namespace App\Event;

use App\Entity\Signalement;
use Symfony\Contracts\EventDispatcher\Event;

class SignalementCreatedEvent extends Event
{
    public const string NAME = 'signalement.created';

    public function __construct(private Signalement $signalement)
    {
    }

    public function getSignalement(): ?Signalement
    {
        return $this->signalement;
    }
}
