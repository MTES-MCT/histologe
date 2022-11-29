<?php

namespace App\Event;

use App\Entity\Affectation;
use Symfony\Contracts\EventDispatcher\Event;

class AffectationAnsweredEvent extends Event
{
    public const NAME = 'affectation.answered';

    public function __construct(private Affectation $affectation, private array $params)
    {
    }

    public function getAffectation(): ?Affectation
    {
        return $this->affectation;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
