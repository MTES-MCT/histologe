<?php

namespace App\Event;

use App\Dto\SignalementAffectationClose;
use App\Entity\Partner;
use Symfony\Contracts\EventDispatcher\Event;

class SignalementClosedEvent extends Event
{
    public const string NAME = 'signalement.closed';

    public function __construct(
        private readonly SignalementAffectationClose $signalementAffectationClose,
        private readonly Partner $partner,
    ) {
    }

    public function getSignalementAffectationClose(): SignalementAffectationClose
    {
        return $this->signalementAffectationClose;
    }

    public function getPartner(): Partner
    {
        return $this->partner;
    }
}
