<?php

namespace App\Event;

use App\Dto\SignalementAffectationClose;
use Symfony\Contracts\EventDispatcher\Event;

class SignalementClosedEvent extends Event
{
    public const string NAME = 'signalement.closed';

    public function __construct(private readonly SignalementAffectationClose $signalementAffectationClose)
    {
    }

    public function getSignalementAffectationClose(): SignalementAffectationClose
    {
        return $this->signalementAffectationClose;
    }
}
