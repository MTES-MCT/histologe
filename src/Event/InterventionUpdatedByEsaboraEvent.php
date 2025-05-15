<?php

namespace App\Event;

use App\Entity\Intervention;
use App\Entity\Suivi;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class InterventionUpdatedByEsaboraEvent extends Event
{
    // public const string NAME = 'intervention.created';
    public const string NAME = 'intervention.updated.esabora';

    private ?Suivi $suivi = null;

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

    public function getSuivi(): ?Suivi
    {
        return $this->suivi;
    }

    public function setSuivi(?Suivi $suivi): void
    {
        $this->suivi = $suivi;
    }
}
