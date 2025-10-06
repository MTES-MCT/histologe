<?php

namespace App\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;

class SignalementBailleur implements UserInterface
{
    public function __construct(
        private readonly string $userIdentifier,
    ) {
    }

    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }

    public function getRoles(): array
    {
        return ['ROLE_BAILLEUR_SIGNALEMENT'];
    }

    public function eraseCredentials(): void
    {
    }
}
