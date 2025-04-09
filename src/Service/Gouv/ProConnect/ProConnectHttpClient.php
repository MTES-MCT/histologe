<?php

namespace App\Service\Gouv\ProConnect;

use App\Service\Gouv\ProConnect\Request\AuthorizationRequest;
use App\Service\Gouv\ProConnect\Request\LogoutRequest;
use App\Service\Gouv\ProConnect\Request\OAuth2TokenRequest;
use App\Service\Gouv\ProConnect\Response\DiscoveryEndpointsResponse;
use App\Service\Gouv\ProConnect\Response\JWKSResponse;
use App\Service\Gouv\ProConnect\Response\OAuth2TokenResponse;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProConnectHttpClient
{
    private ?DiscoveryEndpointsResponse $endpoints = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(env: 'PROCONNECT_SCHEME_PROTOCOL')]
        private readonly string $schemeProtocol,
        #[Autowire(env: 'PROCONNECT_DOMAIN')]
        private readonly string $proconnectDomain,
        #[Autowire(env: 'PROCONNECT_CLIENT_ID')]
        private readonly string $proconnectClientId,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getDiscoveryEndpoints(): DiscoveryEndpointsResponse
    {
        if (null !== $this->endpoints) {
            return $this->endpoints;
        }

        $response = $this->httpClient->request(
            'GET',
            sprintf(
                '%s%s/api/v2/.well-known/openid-configuration',
                $this->schemeProtocol,
                $this->proconnectDomain
            )
        );

        return $this->endpoints = new DiscoveryEndpointsResponse($response->toArray());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getAuthorizationUrl(string $redirectUri, string $state, string $nonce): string
    {
        $authorizationRequest = new AuthorizationRequest(
            $this->proconnectClientId,
            $redirectUri,
            $state,
            $nonce
        );

        return $this->getDiscoveryEndpoints()->authorizationEndpoint.'?'.$authorizationRequest->toQueryString();
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws \Exception
     */
    public function requestToken(OAuth2TokenRequest $tokenRequest): OAuth2TokenResponse
    {
        $response = $this->httpClient->request('POST', $this->getDiscoveryEndpoints()->tokenEndpoint, [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body' => $tokenRequest->toQueryString(),
        ]);

        return new OAuth2TokenResponse($response->toArray());
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function getUserDataJwt(string $accessToken): string
    {
        $response = $this->httpClient->request('GET', $this->getDiscoveryEndpoints()->userInfoEndpoint, [
            'headers' => ['Authorization' => 'Bearer '.$accessToken],
        ]);

        return $response->getContent();
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function getJWKS(): JWKSResponse
    {
        $response = $this->httpClient->request('GET', $this->getDiscoveryEndpoints()->jwksUri, [
            'headers' => ['Accept' => 'application/json'],
        ]);

        return new JWKSResponse($response->getContent());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getLogoutUrl(LogoutRequest $logoutRequest): string
    {
        return $this->getDiscoveryEndpoints()->endSessionEndpoint
            .'?'
            .$logoutRequest->toQueryString();
    }
}
