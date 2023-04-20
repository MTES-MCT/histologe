<?php

namespace App\Event;

use App\Entity\Intervention;
use App\Entity\User;
use DateTimeInterface;
use Symfony\Contracts\EventDispatcher\Event;

class InterventionRescheduledEvent extends Event
{
    public const NAME = 'intervention.rescheduled';

    public function __construct(
        private Intervention $intervention,
        private User $user,
        private DateTimeInterface $previousDate,
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

    public function getPreviousDate(): ?DateTimeInterface
    {
        return $this->previousDate;
    }
}
