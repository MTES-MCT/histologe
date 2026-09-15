<?php

namespace App\Event;

use App\Entity\Intervention;
use App\Entity\Partner;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class InterventionUpdatedByEsaboraEvent extends Event
{
    public const string NAME = 'intervention.updated.esabora';

    public function __construct(
        private readonly Intervention $intervention,
        private readonly User $user,
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

    public function getPartner(): ?Partner
    {
        return $this->partner;
    }
}
