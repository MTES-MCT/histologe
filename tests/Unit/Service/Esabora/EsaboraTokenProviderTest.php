<?php

namespace App\Tests\Unit\Service\Esabora;

use App\Entity\Enum\InterconnectionAuthType;
use App\Entity\Partner;
use App\Service\Interconnection\Esabora\EsaboraTokenProvider;
use App\Service\Interconnection\Esabora\Exception\EsaboraTokenException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseDataCustomMock;

class EsaboraTokenProviderTest extends TestCase
{
    private ArrayAdapter $cache;
    private LoggerInterface $logger;
    /** @var array<string, mixed>[] */
    private array $loggedErrors = [];

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
        $this->loggedErrors = [];
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logger->method('error')->willReturnCallback(function ($message, $context = []) {
            $this->loggedErrors[] = [
                'message' => (string) $message,
                'context' => $context,
            ];
        });
    }

    public function testStaticTokenSuccess(): void
    {
        $mockHttpClient = new MockHttpClient();
        $provider = new EsaboraTokenProvider($mockHttpClient, $this->cache, $this->logger);

        $partner = (new Partner())
            ->setId(1)
            ->setNom('Partenaire Test')
            ->setAuthenticationType(InterconnectionAuthType::STATIC_TOKEN)
            ->setEsaboraToken('my-static-token');

        $token = $provider->getToken($partner);

        $this->assertEquals('my-static-token', $token);
        $this->assertSame(0, $mockHttpClient->getRequestsCount());
    }

    public function testStaticTokenMissingThrowsException(): void
    {
        $mockHttpClient = new MockHttpClient();
        $provider = new EsaboraTokenProvider($mockHttpClient, $this->cache, $this->logger);

        $partner = (new Partner())
            ->setId(1)
            ->setNom('Partenaire Sans Token')
            ->setAuthenticationType(InterconnectionAuthType::STATIC_TOKEN)
            ->setEsaboraToken(null);

        $this->expectException(EsaboraTokenException::class);
        $this->expectExceptionMessage('Aucun token Esabora n\'est configuré pour le partenaire "Partenaire Sans Token".');

        $provider->getToken($partner);
    }

    public function testAuthenticationTypeMissingThrowsException(): void
    {
        $mockHttpClient = new MockHttpClient();
        $provider = new EsaboraTokenProvider($mockHttpClient, $this->cache, $this->logger);

        $partner = (new Partner())
            ->setId(1)
            ->setNom('Partenaire Sans Auth Type')
            ->setAuthenticationType(null);

        $this->expectException(EsaboraTokenException::class);
        $this->expectExceptionMessage('Le mode d\'authentification Esabora n\'est pas configuré pour le partenaire "Partenaire Sans Auth Type".');

        $provider->getToken($partner);
    }

    public function testOAuth2ClientCredentialsSuccess(): void
    {
        $clientSecret = 'super-secret-client-pass-123';
        $accessToken = 'access-token-oauth2-xyz';

        $callback = function ($method, $url, $options) use ($clientSecret, $accessToken) {
            $this->assertEquals('POST', $method);
            $this->assertEquals('https://auth-ext.example.com/oauth2/token', $url);

            $this->assertArrayHasKey('auth_basic', $options);
            $this->assertEquals(['my-client-id', $clientSecret], $options['auth_basic']);

            $this->assertArrayHasKey('body', $options);
            $this->assertStringContainsString('grant_type=client_credentials', $options['body']);
            $this->assertStringContainsString('scope=openid', $options['body']);

            return new MockResponse(json_encode([
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]));
        };

        $mockHttpClient = new MockHttpClient($callback);
        $provider = new EsaboraTokenProvider($mockHttpClient, $this->cache, $this->logger);

        $partner = (new Partner())
            ->setId(42)
            ->setNom('Partenaire Nantes Métropole')
            ->setAuthenticationType(InterconnectionAuthType::OAUTH2_CLIENT_CREDENTIALS)
            ->setOauth2TokenUrl('https://auth-ext.example.com/oauth2/token')
            ->setOauth2ClientId('my-client-id')
            ->setOauth2ClientSecret($clientSecret)
            ->setOauth2Scope('openid');

        $token = $provider->getToken($partner);

        $this->assertEquals($accessToken, $token);
        $this->assertSame(1, $mockHttpClient->getRequestsCount());
    }

    public function testOAuth2TokenCachingAndTtl(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'access_token' => 'cached-access-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]));

        $mockHttpClient = new MockHttpClient([$mockResponse]);
        $provider = new EsaboraTokenProvider($mockHttpClient, $this->cache, $this->logger);

        $partner = (new Partner())
            ->setId(99)
            ->setNom('Partenaire Cached')
            ->setAuthenticationType(InterconnectionAuthType::OAUTH2_CLIENT_CREDENTIALS)
            ->setOauth2TokenUrl('https://auth-ext.example.com/oauth2/token')
            ->setOauth2ClientId('client-id')
            ->setOauth2ClientSecret('client-secret')
            ->setOauth2Scope('openid');

        $firstCallToken = $provider->getToken($partner);
        $this->assertEquals('cached-access-token', $firstCallToken);
        $this->assertSame(1, $mockHttpClient->getRequestsCount());

        $secondCallToken = $provider->getToken($partner);
        $this->assertEquals('cached-access-token', $secondCallToken);
        $this->assertSame(1, $mockHttpClient->getRequestsCount());
    }

    public function testOAuth2DifferentPartnersHaveDistinctCache(): void
    {
        $mockResponse1 = new MockResponse(json_encode([
            'access_token' => 'token-partner-1',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]));
        $mockResponse2 = new MockResponse(json_encode([
            'access_token' => 'token-partner-2',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]));

        $mockHttpClient = new MockHttpClient([$mockResponse1, $mockResponse2]);
        $provider = new EsaboraTokenProvider($mockHttpClient, $this->cache, $this->logger);

        $partner1 = (new Partner())
            ->setId(101)
            ->setNom('Partner 1')
            ->setAuthenticationType(InterconnectionAuthType::OAUTH2_CLIENT_CREDENTIALS)
            ->setOauth2TokenUrl('https://auth.example.com/oauth2/token')
            ->setOauth2ClientId('client-1')
            ->setOauth2ClientSecret('secret-1');

        $partner2 = (new Partner())
            ->setId(102)
            ->setNom('Partner 2')
            ->setAuthenticationType(InterconnectionAuthType::OAUTH2_CLIENT_CREDENTIALS)
            ->setOauth2TokenUrl('https://auth.example.com/oauth2/token')
            ->setOauth2ClientId('client-2')
            ->setOauth2ClientSecret('secret-2');

        $token1 = $provider->getToken($partner1);
        $token2 = $provider->getToken($partner2);

        $this->assertEquals('token-partner-1', $token1);
        $this->assertEquals('token-partner-2', $token2);
        $this->assertSame(2, $mockHttpClient->getRequestsCount());
    }

    public function testOAuth2IncompleteConfigurationThrowsException(): void
    {
        $mockHttpClient = new MockHttpClient();
        $provider = new EsaboraTokenProvider($mockHttpClient, $this->cache, $this->logger);

        // Missing token url
        $partner1 = (new Partner())
            ->setId(1)
            ->setNom('Partenaire Incomplet 1')
            ->setAuthenticationType(InterconnectionAuthType::OAUTH2_CLIENT_CREDENTIALS)
            ->setOauth2ClientId('client-id')
            ->setOauth2ClientSecret('client-secret');

        try {
            $provider->getToken($partner1);
            $this->fail('Expected EsaboraTokenException was not thrown.');
        } catch (EsaboraTokenException $exception) {
            $this->assertStringContainsString('La configuration OAuth2 est incomplète', $exception->getMessage());
        }

        // Missing client ID
        $partner2 = (new Partner())
            ->setId(2)
            ->setNom('Partenaire Incomplet 2')
            ->setAuthenticationType(InterconnectionAuthType::OAUTH2_CLIENT_CREDENTIALS)
            ->setOauth2TokenUrl('https://auth.example.com')
            ->setOauth2ClientSecret('client-secret');

        try {
            $provider->getToken($partner2);
            $this->fail('Expected EsaboraTokenException was not thrown.');
        } catch (EsaboraTokenException $exception) {
            $this->assertStringContainsString('La configuration OAuth2 est incomplète', $exception->getMessage());
        }

        // Missing client secret
        $partner3 = (new Partner())
            ->setId(3)
            ->setNom('Partenaire Incomplet 3')
            ->setAuthenticationType(InterconnectionAuthType::OAUTH2_CLIENT_CREDENTIALS)
            ->setOauth2TokenUrl('https://auth.example.com')
            ->setOauth2ClientId('client-id');

        try {
            $provider->getToken($partner3);
            $this->fail('Expected EsaboraTokenException was not thrown.');
        } catch (EsaboraTokenException $exception) {
            $this->assertStringContainsString('La configuration OAuth2 est incomplète', $exception->getMessage());
        }
    }

    public function testOAuth2HttpErrorThrowsException(): void
    {
        $clientSecret = 'sensitive-secret-value-999';
        $mockResponse = new MockResponse('Unauthorized', ['http_code' => 401]);
        $mockHttpClient = new MockHttpClient($mockResponse);
        $provider = new EsaboraTokenProvider($mockHttpClient, $this->cache, $this->logger);

        $partner = (new Partner())
            ->setId(1)
            ->setNom('Partenaire Erreur HTTP')
            ->setAuthenticationType(InterconnectionAuthType::OAUTH2_CLIENT_CREDENTIALS)
            ->setOauth2TokenUrl('https://auth.example.com/oauth2/token')
            ->setOauth2ClientId('client-id')
            ->setOauth2ClientSecret($clientSecret);

        try {
            $provider->getToken($partner);
            $this->fail('Expected EsaboraTokenException was not thrown.');
        } catch (EsaboraTokenException $exception) {
            $this->assertStringContainsString('Erreur HTTP 401', $exception->getMessage());
            $this->assertStringNotContainsString($clientSecret, $exception->getMessage());
        }

        foreach ($this->loggedErrors as $log) {
            $this->assertStringNotContainsString($clientSecret, $log['message']);
        }
    }

    public function testOAuth2MissingAccessTokenInResponseThrowsException(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'error' => 'invalid_client',
            'error_description' => 'Client authentication failed',
        ]));
        $mockHttpClient = new MockHttpClient($mockResponse);
        $provider = new EsaboraTokenProvider($mockHttpClient, $this->cache, $this->logger);

        $partner = (new Partner())
            ->setId(1)
            ->setNom('Partenaire Sans Access Token')
            ->setAuthenticationType(InterconnectionAuthType::OAUTH2_CLIENT_CREDENTIALS)
            ->setOauth2TokenUrl('https://auth.example.com/oauth2/token')
            ->setOauth2ClientId('client-id')
            ->setOauth2ClientSecret('client-secret');

        $this->expectException(EsaboraTokenException::class);
        $this->expectExceptionMessage('L\'access_token est absent de la réponse OAuth2 pour le partenaire "Partenaire Sans Access Token".');

        $provider->getToken($partner);
    }

    public function testOAuth2InvalidJsonResponseThrowsException(): void
    {
        $mockResponse = new MockResponse('<html><body>Internal Server Error</body></html>');
        $mockHttpClient = new MockHttpClient($mockResponse);
        $provider = new EsaboraTokenProvider($mockHttpClient, $this->cache, $this->logger);

        $partner = (new Partner())
            ->setId(1)
            ->setNom('Partenaire HTML Response')
            ->setAuthenticationType(InterconnectionAuthType::OAUTH2_CLIENT_CREDENTIALS)
            ->setOauth2TokenUrl('https://auth.example.com/oauth2/token')
            ->setOauth2ClientId('client-id')
            ->setOauth2ClientSecret('client-secret');

        $this->expectException(EsaboraTokenException::class);
        $provider->getToken($partner);
    }

    public function testNoSecretOrTokenInLogsOrExceptionMessages(): void
    {
        $clientSecret = 'ultra-secret-password-xyz';
        $accessToken = 'my-super-secret-access-token';

        $mockResponse = new MockResponse('Internal Server Error', ['http_code' => 500]);
        $mockHttpClient = new MockHttpClient($mockResponse);
        $provider = new EsaboraTokenProvider($mockHttpClient, $this->cache, $this->logger);

        $partner = (new Partner())
            ->setId(1)
            ->setNom('Partenaire Test Sécurité')
            ->setAuthenticationType(InterconnectionAuthType::OAUTH2_CLIENT_CREDENTIALS)
            ->setOauth2TokenUrl('https://auth.example.com/oauth2/token')
            ->setOauth2ClientId('client-id')
            ->setOauth2ClientSecret($clientSecret);

        try {
            $provider->getToken($partner);
        } catch (EsaboraTokenException $exception) {
            $this->assertStringNotContainsString($clientSecret, $exception->getMessage());
            $this->assertStringNotContainsString($accessToken, $exception->getMessage());
        }

        foreach ($this->loggedErrors as $log) {
            $this->assertStringNotContainsString($clientSecret, $log['message']);
            $this->assertStringNotContainsString($accessToken, $log['message']);
        }
    }
}
