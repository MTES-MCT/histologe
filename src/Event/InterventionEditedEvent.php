<?php

namespace App\Event;

use App\Entity\Intervention;
use App\Entity\Partner;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class InterventionEditedEvent extends Event
{
    public const string NAME = 'intervention.edited';

    public function __construct(
        private readonly Intervention $intervention,
        private readonly User $user,
        private readonly bool $isUsagerNotified,
        private readonly Partner $partner,
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

    public function getPartner(): ?Partner
    {
        return $this->partner;
    }
}
