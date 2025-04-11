<?php

namespace App\Service\Gouv\ProConnect;

use Random\RandomException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ProConnectContext
{
    public const string SESSION_KEY_STATE = 'pr_state';
    public const string SESSION_KEY_NONCE = 'pr_nonce';

    public function __construct(
        private readonly RequestStack $requestStack,
        #[Autowire(env: 'PROCONNECT_DOMAIN')]
        private readonly string $proconnectDomain,
    ) {
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }

    /**
     * @throws RandomException
     */
    public function generateState(): string
    {
        $state = $this->isMockEnvironment() ? 'fake_state' : bin2hex(random_bytes(16));
        $this->getSession()->set(self::SESSION_KEY_STATE, $state);

        return $state;
    }

    public function getState(): ?string
    {
        return $this->getSession()->get(self::SESSION_KEY_STATE);
    }

    /**
     * @throws RandomException
     */
    public function generateNonce(): string
    {
        $nonce = $this->isMockEnvironment() ? 'fake_nonce' : bin2hex(random_bytes(16));
        $this->getSession()->set(self::SESSION_KEY_NONCE, $nonce);

        return $nonce;
    }

    public function getNonce(): ?string
    {
        return $this->getSession()->get(self::SESSION_KEY_NONCE);
    }

    public function isValidState(?string $state): bool
    {
        return null !== $state && $state === $this->getState();
    }

    public function clear(): void
    {
        $this->getSession()->remove(self::SESSION_KEY_STATE);
        $this->getSession()->remove(self::SESSION_KEY_NONCE);
    }

    private function isMockEnvironment(): bool
    {
        return str_contains($this->proconnectDomain, 'wiremock');
    }
}
