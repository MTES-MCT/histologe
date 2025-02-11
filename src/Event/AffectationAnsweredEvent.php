<?php

namespace App\Event;

use App\Entity\Affectation;
use App\Entity\Enum\MotifRefus;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class AffectationAnsweredEvent extends Event
{
    public const string NAME = 'affectation.answered';

    public function __construct(
        private readonly Affectation $affectation,
        private readonly User $user,
        private readonly ?int $status,
        private readonly ?MotifRefus $motifRefus,
        private readonly ?string $message,
    ) {
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getAffectation(): ?Affectation
    {
        return $this->affectation;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function getMotifRefus(): ?MotifRefus
    {
        return $this->motifRefus;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }
}
