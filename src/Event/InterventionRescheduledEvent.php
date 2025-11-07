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
        private readonly Intervention $intervention,
        private readonly User $user,
        private readonly \DateTimeImmutable $previousDate,
        private readonly ?Partner $partner = null,
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
