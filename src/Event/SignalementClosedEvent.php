<?php

namespace App\Event;

use App\Entity\Affectation;
use App\Entity\Signalement;
use Symfony\Contracts\EventDispatcher\Event;

class SignalementClosedEvent extends Event
{
    public const NAME = 'signalement.closed';

    public function __construct(private Signalement|Affectation $entity, private array $params)
    {
    }

    public function getSignalement(): ?Signalement
    {
        return $this->entity instanceof Signalement ? $this->entity : null;
    }

    public function getAffectation(): ?Affectation
    {
        return $this->entity instanceof Affectation ? $this->entity : null;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
