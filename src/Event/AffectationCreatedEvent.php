<?php

namespace App\Event;

use App\Entity\Affectation;
use Symfony\Contracts\EventDispatcher\Event;

class AffectationCreatedEvent extends Event
{
    public const string NAME = 'affectation.created';

    public function __construct(private Affectation $affectation)
    {
    }

    public function getAffectation(): ?Affectation
    {
        return $this->affectation;
    }
}
