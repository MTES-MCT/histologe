<?php

namespace App\Service\Gouv\ProConnect;

use Random\RandomException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class ProConnectContext
{
    /**
     * Le paramètre "state" est utilisé pour prévenir les attaques de type CSRF (Cross-Site Request Forgery).
     * Il permet de vérifier que la réponse reçue de ProConnect correspond bien à une requête initiée par l'application.
     */
    public const string SESSION_KEY_STATE = 'proconnect_state';

    /**
     * Le paramètre "nonce" (Number used once) est utilisé pour lier la requête d'authentification au token retourné,
     * et prévenir les attaques de type "replay". Il est inclus dans l'ID Token et doit être validé.
     */
    public const string SESSION_KEY_NONCE = 'proconnect_nonce';

    /**
     * Le paramètre "id_token" contient le JWT d'identité retourné par ProConnect après authentification.
     * Ce token est utilisé pour effectuer la déconnexion distante (logout OIDC) et contient les claims utilisateur.
     */
    public const string SESSION_KEY_ID_TOKEN = 'proconnect_id_token';

    private string $redirectLoginUrl;
    private string $redirectLogoutUrl;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestContext $requestContext,
        #[Autowire(env: 'PROCONNECT_DOMAIN')]
        private readonly string $proconnectDomain,
        #[Autowire(env: 'APP_URL')]
        private readonly string $appUrl,
    ) {
        if (!$this->isLocalEnvironment()) {
            $this->requestContext->setScheme('https');
        }

        $this->redirectLoginUrl = $this->urlGenerator->generate(
            'app_user_proconnect_login_callback',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->redirectLogoutUrl = $this->urlGenerator->generate(
            'app_logout',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    public function getRedirectLoginUrl(): string
    {
        return $this->redirectLoginUrl;
    }

    public function getRedirectLogoutUrl(): string
    {
        return $this->redirectLogoutUrl;
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

    public function setIdToken(string $idToken): void
    {
        $this->getSession()->set(self::SESSION_KEY_ID_TOKEN, $idToken);
    }

    public function getIdToken(): ?string
    {
        return $this->getSession()->get(self::SESSION_KEY_ID_TOKEN);
    }

    public function clearSession(): void
    {
        $this->getSession()->remove(self::SESSION_KEY_STATE);
        $this->getSession()->remove(self::SESSION_KEY_NONCE);
        $this->getSession()->remove(self::SESSION_KEY_ID_TOKEN);
    }

    private function isMockEnvironment(): bool
    {
        return str_contains($this->proconnectDomain, 'wiremock');
    }

    private function isLocalEnvironment(): bool
    {
        return str_contains($this->appUrl, 'localhost');
    }
}
