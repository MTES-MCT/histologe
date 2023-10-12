<?php

namespace App\Event;

use App\Entity\SignalementDraft;
use Symfony\Contracts\EventDispatcher\Event;

class SignalementDraftCompletedEvent extends Event
{
    public const NAME = 'signalement_draft.completed';

    public function __construct(
        private ?SignalementDraft $signalementDraft,
    ) {
    }

    public function getSignalementDraft(): ?SignalementDraft
    {
        return $this->signalementDraft;
    }
}
