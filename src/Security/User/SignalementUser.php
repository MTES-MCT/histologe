<?php

namespace App\Security\User;

use App\Entity\Territory;
use App\Entity\User;
use App\Manager\UserManager;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SignalementUser implements UserInterface, TwoFactorInterface
{
    private string $codeSuivi;
    private string $type;
    /**
     * @var array<string>
     */
    private array $roles = [];
    private ?string $authCode;

    public function __construct(
        private readonly string $userIdentifier,
        private readonly User $user,
        private readonly ?string $email = null,
    ) {
        [$this->codeSuivi, $this->type] = explode(':', $this->userIdentifier);
        $roles = ['ROLE_SUIVI_SIGNALEMENT'];
        $roles[] = UserManager::DECLARANT === $this->type ? 'ROLE_DECLARANT' : 'ROLE_OCCUPANT';
        $userRoles = $this->user->getRoles();
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

    public function getUser(): User
    {
        return $this->user;
    }

    public function isEmailAuthEnabled(): bool
    {
        return $this->user->isUsager();
    }

    public function getEmailAuthRecipient(): string
    {
        return $this->email;
    }

    public function getEmailAuthCode(): string
    {
        if (null === $this->authCode) {
            throw new \LogicException('The email authentication code was not set');
        }

        return $this->authCode;
    }

    public function setEmailAuthCode(?string $authCode): void
    {
        $this->authCode = $authCode;
    }
}
