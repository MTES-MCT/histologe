<?php

namespace App\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;

class ServiceSecoursUser implements UserInterface
{
    /**
     * @param non-empty-string $userIdentifier
     */
    public function __construct(
        private readonly string $userIdentifier,
        private readonly ?string $email,
        private readonly string $serviceSecoursRouteUuid,
        private readonly ?string $matricule = null,
        private readonly ?string $fullname = null,
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getServiceSecoursRouteUuid(): string
    {
        return $this->serviceSecoursRouteUuid;
    }

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function getRoles(): array
    {
        return ['ROLE_SERVICE_SECOURS'];
    }

    public function eraseCredentials(): void
    {
        // Nothing to erase: ServiceSecoursUser does not store temporary sensitive credentials.
    }
}
