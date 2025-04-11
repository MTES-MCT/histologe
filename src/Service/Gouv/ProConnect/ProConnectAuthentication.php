<?php

namespace App\Service\Gouv\ProConnect;

use App\Exception\ProConnect\ProConnectException;
use App\Service\Gouv\ProConnect\Model\ProConnectUser;
use App\Service\Gouv\ProConnect\Request\CallbackRequest;
use App\Service\Gouv\ProConnect\Request\OAuth2TokenRequest;
use Random\RandomException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ProConnectAuthentication
{
    private string $redirectUrl;

    public function __construct(
        private readonly ProConnectHttpClient $proConnectHttpClient,
        private readonly ProConnectContext $proConnectContext,
        private readonly ProConnectJwtValidator $proConnectJwtValidator,
        private readonly ProConnectJwtParser $proConnectJwtParser,
        private readonly RouterInterface $router,
        #[Autowire(env: 'PROCONNECT_CLIENT_ID')]
        private readonly string $proconnectClientId,
        #[Autowire(env: 'PROCONNECT_CLIENT_SECRET')]
        private readonly string $proconnectClientSecret,
    ) {
        $this->redirectUrl = $this->router->generate(
            'app_user_proconnect_callback',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
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
        $state = $this->proConnectContext->generateState();
        $nonce = $this->proConnectContext->generateNonce();

        return $this->proConnectHttpClient->getAuthorizationUrl(
            redirectUri: $this->redirectUrl,
            state: $state,
            nonce: $nonce
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
    public function authenticateFromCallback(array $params): ProConnectUser
    {
        $callbackRequest = CallbackRequest::fromArray($params);

        if (!$this->proConnectContext->isValidState($callbackRequest->state)) {
            throw new ProConnectException('Le paramètre state est invalide ou absent.');
        }

        $tokenRequest = new OAuth2TokenRequest(
            clientId: $this->proconnectClientId,
            clientSecret: $this->proconnectClientSecret,
            redirectUri: $this->redirectUrl,
            code: $callbackRequest->code,
        );
        $tokenResponse = $this->proConnectHttpClient->requestToken($tokenRequest);

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
}
