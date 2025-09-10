<?php

namespace App\Event;

use App\Entity\Intervention;
use App\Entity\Partner;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class InterventionRescheduledEvent extends Event
{
    public const string NAME = 'intervention.rescheduled';

    public function __construct(
        private Intervention $intervention,
        private User $user,
        private \DateTimeImmutable $previousDate,
        private Partner $partner,
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

    public function getPreviousDate(): ?\DateTimeImmutable
    {
        return $this->previousDate;
    }

    public function getPartner(): ?Partner
    {
        return $this->partner;
    }
}
