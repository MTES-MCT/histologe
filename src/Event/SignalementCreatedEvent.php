<?php

namespace App\Event;

use App\Entity\Signalement;
use Symfony\Contracts\EventDispatcher\Event;

class SignalementCreatedEvent extends Event
{
    public const NAME = 'signalement.created';

    public function __construct(private Signalement $entity)
    {
    }

    public function getSignalement(): ?Signalement
    {
        return $this->entity;
    }
}
