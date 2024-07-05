<?php

namespace App\Event;

use App\Entity\Intervention;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class InterventionCreatedEvent extends Event
{
    public const NAME = 'intervention.created';
    public const UPDATED_BY_ESABORA = 'intervention.updated.esabora';

    public function __construct(
        private readonly Intervention $intervention,
        private readonly User $user,
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
