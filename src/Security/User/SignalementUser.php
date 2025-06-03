<?php

namespace App\Security\User;

use App\Entity\Territory;
use App\Entity\User;
use App\Manager\UserManager;
use Symfony\Component\Security\Core\User\UserInterface;

class SignalementUser implements UserInterface
{
    private string $codeSuivi;
    private string $type;
    private array $roles = [];

    public function __construct(
        private readonly string $userIdentifier,
        private readonly ?string $email = null,
        private readonly ?User $user = null,
    ) {
        [$this->codeSuivi, $this->type] = explode(':', $this->userIdentifier);
        $roles = ['ROLE_SUIVI_SIGNALEMENT'];
        $roles[] = UserManager::DECLARANT === $this->type ? 'ROLE_DECLARANT' : 'ROLE_OCCUPANT';
        $userRoles = $this->user?->getRoles() ?? [];
        $this->roles = array_values(array_unique([...$roles, ...$userRoles]));
    }

    public function getUserIdentifier(): string
    {
        return $this->codeSuivi.':'.$this->type;
    }

    public function getRoles(): array
    {
        return $this->roles;
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

    public function getCodeSuivi(): string
    {
        return $this->codeSuivi;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
}
