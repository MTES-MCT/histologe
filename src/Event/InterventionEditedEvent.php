<?php

namespace App\Event;

use App\Entity\Intervention;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class InterventionEditedEvent extends Event
{
    public const NAME = 'intervention.edited';

    public function __construct(
        private Intervention $intervention,
        private User $user,
        private bool $isUsagerNotified,
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

    public function isUsagerNotified(): ?bool
    {
        return $this->isUsagerNotified;
    }
}
