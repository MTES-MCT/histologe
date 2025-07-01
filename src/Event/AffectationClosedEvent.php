<?php

namespace App\Event;

use App\Entity\Affectation;
use App\Entity\File;
use App\Entity\User;

class AffectationClosedEvent
{
    public const string NAME = 'affectation.closed';

    /**
     * @param iterable<File> $files
     */
    public function __construct(
        private readonly Affectation $affectation,
        private readonly User $user,
        private readonly ?string $message = null,
        private readonly iterable $files = [],
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

    /**
     * @return iterable<File>
     */
    public function getFiles(): iterable
    {
        return $this->files;
    }
}
