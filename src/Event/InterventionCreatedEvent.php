<?php

namespace App\Event;

use App\Entity\Intervention;
use App\Entity\Partner;
use App\Entity\Suivi;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class InterventionCreatedEvent extends Event
{
    public const string NAME = 'intervention.created';

    private ?Suivi $suivi = null;

    public function __construct(
        private readonly Intervention $intervention,
        private readonly User $user,
        private readonly Partner $partner,
        private readonly ?string $source = null,
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

    public function getSuivi(): ?Suivi
    {
        return $this->suivi;
    }

    public function setSuivi(?Suivi $suivi): void
    {
        $this->suivi = $suivi;
    }

    public function getPartner(): ?Partner
    {
        return $this->partner;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }
}
