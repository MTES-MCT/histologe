<?php

namespace App\Tests\Unit\Service\Gouv\ProConnect;

use App\Service\Gouv\ProConnect\Model\JWK;
use App\Service\Gouv\ProConnect\ProConnectHttpClient;
use App\Service\Gouv\ProConnect\Request\LogoutRequest;
use App\Service\Gouv\ProConnect\Request\OAuth2TokenRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ProConnectHttpClientTest extends TestCase
{
    public string $jwksFile;
    public string $discoveryFile;
    public ProConnectHttpClient $proConnectHttpClient;

    protected function setUp(): void
    {
        $this->jwksFile = (string) file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/jwks.json');
        $this->discoveryFile = (string) file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/openid-configuration.json');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testGetAuthorizationUrl(): void
    {
        $discoveryResponse = new MockResponse($this->discoveryFile);
        $mockHttpClient = new MockHttpClient([$discoveryResponse], 'http://localhost:8082');

        $client = new ProConnectHttpClient(
            httpClient: $mockHttpClient,
            schemeProtocol: 'http',
            proconnectDomain: 'localhost:8082',
            proconnectClientId: 'client-id'
        );

        $url = $client->getAuthorizationUrl(
            redirectUri: 'https://myapp.com/callback',
            state: 'abc123',
            nonce: 'nonce123'
        );

        $this->assertStringStartsWith('http://localhost:8082/authorize?', $url);
        $this->assertStringContainsString('client_id=client-id', $url);
        $this->assertStringContainsString('redirect_uri=https%3A%2F%2Fmyapp.com%2Fcallback', $url);
        $this->assertStringContainsString('state=abc123', $url);
        $this->assertStringContainsString('nonce=nonce123', $url);
    }

    public function testRequestToken(): void
    {
        $discoveryResponse = new MockResponse((string) json_encode([
            'authorization_endpoint' => 'https://proconnect.gouv.fr/authorize',
            'token_endpoint' => 'https://proconnect.gouv.fr/token',
            'userinfo_endpoint' => 'https://proconnect.gouv.fr/userinfo',
            'jwks_uri' => 'https://proconnect.gouv.fr/jwks',
        ]));

        $tokenResponse = new MockResponse((string) json_encode([
            'access_token' => 'access123',
            'id_token' => 'idtoken123',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
        ]));

        $mockHttpClient = new MockHttpClient([$discoveryResponse, $tokenResponse]);

        $client = new ProConnectHttpClient(
            httpClient: $mockHttpClient,
            schemeProtocol: 'http',
            proconnectDomain: 'proconnect.gouv.fr',
            proconnectClientId: 'client-id'
        );

        $request = new OAuth2TokenRequest(
            clientId: 'client-id',
            clientSecret: 'secret',
            redirectUri: 'https://myapp.com/callback',
            code: 'authcode123'
        );

        $response = $client->requestToken($request);

        $this->assertEquals('access123', $response->accessToken);
        $this->assertEquals('idtoken123', $response->idToken);
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetDiscoveryEndpointsReturnsSameInstanceOnSecondCall(): void
    {
        $discoveryResponse = new MockResponse($this->discoveryFile);
        $mockHttpClient = new MockHttpClient([$discoveryResponse]);

        $client = new ProConnectHttpClient(
            httpClient: $mockHttpClient,
            schemeProtocol: 'http',
            proconnectDomain: 'proconnect.gouv.fr',
            proconnectClientId: 'client-id'
        );

        $firstCall = $client->getDiscoveryEndpoints();
        $secondCall = $client->getDiscoveryEndpoints();

        $this->assertSame($firstCall, $secondCall);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testGetUserDataJwt(): void
    {
        $discoveryResponse = new MockResponse($this->discoveryFile);
        $jwtResponse = new MockResponse('eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...');

        $mockHttpClient = new MockHttpClient([$discoveryResponse, $jwtResponse]);
        $client = new ProConnectHttpClient(
            httpClient: $mockHttpClient,
            schemeProtocol: 'http',
            proconnectDomain: 'proconnect.gouv.fr',
            proconnectClientId: 'client-id'
        );

        $jwt = $client->getUserDataJwt('access-token');
        $this->assertStringContainsString('eyJhbGciOi', $jwt);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testGetJWKS(): void
    {
        $jwkResponse = new MockResponse($this->jwksFile);
        $discoveryResponse = new MockResponse($this->discoveryFile);
        $mockHttpClient = new MockHttpClient([$discoveryResponse, $jwkResponse]);
        $client = new ProConnectHttpClient(
            httpClient: $mockHttpClient,
            schemeProtocol: 'http',
            proconnectDomain: 'proconnect.gouv.fr',
            proconnectClientId: 'client-id'
        );
        $jwks = $client->getJWKS();
        /** @var JWK $jwk */
        $jwk = current($jwks->getKeys());
        $this->assertArrayHasKey('kty', $jwk->toArray());
        $this->assertArrayHasKey('alg', $jwk->toArray());
        $this->assertArrayHasKey('use', $jwk->toArray());
        $this->assertArrayHasKey('kid', $jwk->toArray());
        $this->assertArrayHasKey('n', $jwk->toArray());
        $this->assertArrayHasKey('e', $jwk->toArray());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testGetLogoutUrl(): void
    {
        $discoveryResponse = new MockResponse($this->discoveryFile);
        $mockHttpClient = new MockHttpClient([$discoveryResponse]);
        $client = new ProConnectHttpClient(
            httpClient: $mockHttpClient,
            schemeProtocol: 'http',
            proconnectDomain: 'proconnect.gouv.fr',
            proconnectClientId: 'client-id'
        );

        $logoutRequest = new LogoutRequest('fake_token', 'fake_state', 'https://myapp.com/logout');
        $url = $client->getLogoutUrl($logoutRequest);
        $urlParsed = parse_url($url);
        if (false === $urlParsed) {
            self::fail('Invalid URL returned: '.$url);
        }
        if (!isset($urlParsed['query'])) {
            self::fail('URL has no query part: '.$url);
        }
        parse_str($urlParsed['query'], $params);
        $this->assertEquals('fake_token', $params['id_token_hint']);
        $this->assertEquals('fake_state', $params['state']);
        $this->assertEquals('https://myapp.com/logout', $params['post_logout_redirect_uri']);
    }
}
