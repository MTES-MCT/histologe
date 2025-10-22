<?php

namespace App\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;

class SignalementBailleur implements UserInterface
{
    /**
     * @param non-empty-string $userIdentifier
     */
    public function __construct(
        private readonly string $userIdentifier,
    ) {
    }

    /**
     * @return non-empty-string
     */
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
