<?php

namespace App\Service\Gouv\ProConnect;

use App\Exception\ProConnect\ProConnectException;
use App\Service\Gouv\ProConnect\Model\ProConnectUser;
use App\Service\Gouv\ProConnect\Request\CallbackRequest;
use App\Service\Gouv\ProConnect\Request\LogoutRequest;
use App\Service\Gouv\ProConnect\Request\OAuth2TokenRequest;
use Random\RandomException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ProConnectAuthentication
{
    public function __construct(
        private readonly ProConnectHttpClient $proConnectHttpClient,
        private readonly ProConnectContext $proConnectContext,
        private readonly ProConnectJwtValidator $proConnectJwtValidator,
        private readonly ProConnectJwtParser $proConnectJwtParser,
        #[Autowire(env: 'PROCONNECT_CLIENT_ID')]
        private readonly string $proconnectClientId,
        #[Autowire(env: 'PROCONNECT_CLIENT_SECRET')]
        private readonly string $proconnectClientSecret,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws RandomException
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getAuthorizationUrl(): string
    {
        return $this->proConnectHttpClient->getAuthorizationUrl(
            redirectUri: $this->proConnectContext->getRedirectLoginUrl(),
            state: $this->proConnectContext->generateState(),
            nonce: $this->proConnectContext->generateNonce(),
        );
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws ProConnectException
     * @throws \Exception
     */
    public function authenticateFromCallback(CallbackRequest $callbackRequest): ProConnectUser
    {
        if (!$this->proConnectContext->isValidState($callbackRequest->state)) {
            throw new ProConnectException('Le paramètre state est invalide ou absent.');
        }

        $tokenRequest = new OAuth2TokenRequest(
            clientId: $this->proconnectClientId,
            clientSecret: $this->proconnectClientSecret,
            redirectUri: $this->proConnectContext->getRedirectLoginUrl(),
            code: $callbackRequest->code,
        );
        $tokenResponse = $this->proConnectHttpClient->requestToken($tokenRequest);
        $this->proConnectContext->setIdToken($tokenResponse->idToken);

        $jwks = $this->proConnectHttpClient->getJWKS();
        $isValid = $this->proConnectJwtValidator->validate(
            $jwks,
            $tokenResponse->idToken,
            $this->proConnectContext->getNonce()
        );

        if (!$isValid) {
            throw new ProConnectException('Le token JWT reçu est invalide.');
        }

        $userDataJwt = $this->proConnectHttpClient->getUserDataJwt($tokenResponse->accessToken);
        $proConnectUserData = $this->proConnectJwtParser->parse($userDataJwt);

        return new ProConnectUser($proConnectUserData);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ProConnectException
     */
    public function getLogoutUrl(): string
    {
        return $this->proConnectHttpClient->getLogoutUrl(
            new LogoutRequest(
                $this->proConnectContext->getIdToken(),
                $this->proConnectContext->getState(),
                $this->proConnectContext->getRedirectLogoutUrl(),
            )
        );
    }
}
