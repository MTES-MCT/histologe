<?php

namespace App\Event;

use App\Entity\Intervention;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class InterventionCreatedEvent extends Event
{
    public const NAME = 'intervention.created';

    public function __construct(
        private Intervention $intervention,
        private User $user,
    ) {
    }

    public function getIntervention(): ?Intervention
    {
        return $this->intervention;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
}
