<?php

namespace App\Event;

use App\Entity\Affectation;
use App\Entity\User;

class AffectationClosedEvent
{
    public const string NAME = 'affectation.closed';

    public function __construct(
        private readonly Affectation $affectation,
        private readonly User $user,
        private readonly ?string $message = null,
    ) {
    }

    public function getAffectation(): Affectation
    {
        return $this->affectation;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }
}
