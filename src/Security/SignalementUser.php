<?php

namespace App\Security;

use App\Entity\Territory;
use Symfony\Component\Security\Core\User\UserInterface;

class SignalementUser implements UserInterface
{
    public function __construct(
        private string $codeSuivi,
    ) {
    }

    public function getUserIdentifier(): string
    {
        return $this->codeSuivi;
    }

    public function getRoles(): array
    {
        return ['ROLE_SUIVI_SIGNALEMENT'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getPassword(): ?string
    {
        return null;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function getFirstTerritory(): ?Territory
    {
        return null;
    }
}
