<?php

namespace App\Tests\Unit\Service\Gouv\ProConnect;

use App\Service\Gouv\ProConnect\ProConnectAuthentication;
use App\Service\Gouv\ProConnect\ProConnectContext;
use App\Service\Gouv\ProConnect\ProConnectHttpClient;
use App\Service\Gouv\ProConnect\ProConnectJwtParser;
use App\Service\Gouv\ProConnect\ProConnectJwtValidator;
use App\Service\Gouv\ProConnect\Request\CallbackRequest;
use App\Service\Gouv\ProConnect\Request\LogoutRequest;
use App\Service\Gouv\ProConnect\Response\JWKSResponse;
use App\Service\Gouv\ProConnect\Response\OAuth2TokenResponse;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use PHPUnit\Framework\MockObject\MockObject;

class ProConnectAuthenticationTest extends KernelTestCase
{
    public function testAuthenticateFromCallbackReturnsValidUser(): void
    {
        /** @var UrlGeneratorInterface&MockObject $urlGenerator */
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $requestContext = new RequestContext();

        $context = new ProConnectContext(
            $this->createRequestStackWithSession(),
            $urlGenerator,
            $requestContext,
            'wiremock',
            'localhost'
        );

        /** @var ProConnectHttpClient&MockObject $httpClient */
        $httpClient = $this->createMock(ProConnectHttpClient::class);
        /** @var ProConnectJwtValidator&MockObject $jwtValidator */
        $jwtValidator = $this->createMock(ProConnectJwtValidator::class);
        /** @var ProConnectJwtParser&MockObject $jwtParser */
        $jwtParser = $this->createMock(ProConnectJwtParser::class);

        $httpClient
            ->expects(self::once())
            ->method('requestToken')
            ->willReturn(new OAuth2TokenResponse(['access_token' => 'dummy', 'id_token' => 'dummy']));

        $jwksFile = file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/jwks.json');
        $httpClient
            ->expects(self::once())
            ->method('getJWKS')
            ->willReturn(new JWKSResponse((string) $jwksFile));

        $jwtValidator
            ->expects(self::once())
            ->method('validate')
            ->willReturn(true);

        $proConnectData = [
            'sub' => '1234',
            'uid' => '1234',
            'email' => 'proconnect@signal-logement.fr',
        ];
        $httpClient
            ->expects(self::once())
            ->method('getUserDataJwt')
            ->willReturn(json_encode($proConnectData));

        $jwtParser
            ->expects(self::once())
            ->method('parse')
            ->willReturn($proConnectData);

        $proConnectAuthentication = new ProConnectAuthentication(
            $httpClient,
            $context,
            $jwtValidator,
            $jwtParser,
            'client_id',
            'client_secret',
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $user = $proConnectAuthentication->authenticateFromCallback(new CallbackRequest('valid_code', 'valid_state'));
        $this->assertSame('1234', $user->uid);
        $this->assertSame('proconnect@signal-logement.fr', $user->email);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testGetLogoutUrlReturnsExpectedUrl(): void
    {
        /** @var UrlGeneratorInterface&MockObject $urlGenerator */
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->willReturnCallback(fn ($routeName) => match ($routeName) {
                'app_user_proconnect_login_callback' => 'https://myapp.com/proconnect/login-callback',
                'app_logout' => 'https://myapp.com/logout',
                default => throw new \LogicException("Unexpected route: $routeName"),
            });

        $context = new ProConnectContext(
            $this->createRequestStackWithSession(),
            $urlGenerator,
            new RequestContext(),
            'wiremock',
            'http://localhost'
        );
        $context->setIdToken('valid_id_token');

        $expectedUrl = 'https://provider/logout?id_token_hint=valid_id_token&state=valid_state&post_logout_redirect_uri=https%3A%2F%2Fmyapp.com%2Flogout';

        /** @var ProConnectHttpClient&MockObject $httpClient */
        $httpClient = $this->createMock(ProConnectHttpClient::class);
        $httpClient
            ->expects(self::once())
            ->method('getLogoutUrl')
            ->with($this->callback(function (LogoutRequest $request) {
                return 'valid_id_token' === $request->idTokenHint
                    && 'valid_state' === $request->state
                    && 'https://myapp.com/logout' === $request->postLogoutRedirectUri;
            }))
            ->willReturn($expectedUrl);

        /** @var ProConnectJwtValidator&MockObject $jwtValidator */
        $jwtValidator = $this->createMock(ProConnectJwtValidator::class);
        /** @var ProConnectJwtParser&MockObject $jwtParser */
        $jwtParser = $this->createMock(ProConnectJwtParser::class);
        $proConnectAuthentication = new ProConnectAuthentication(
            $httpClient,
            $context,
            $jwtValidator,
            $jwtParser,
            'client_id',
            'client_secret',
        );

        $result = $proConnectAuthentication->getLogoutUrl();
        $this->assertSame($expectedUrl, $result);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws RandomException
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testGetAuthorizationUrlReturnsExpectedUrl(): void
    {
        $expectedState = 'mock_state';
        $expectedNonce = 'mock_nonce';
        $expectedRedirectUri = 'https://myapp.com/proconnect/login-callback';
        $expectedAuthorizationUrl = $expectedRedirectUri.'?state='.$expectedState.'&nonce='.$expectedNonce;

        /** @var ProConnectContext&MockObject $contextMock */
        $contextMock = $this->createMock(ProConnectContext::class);
        $contextMock->method('getRedirectLoginUrl')->willReturn($expectedRedirectUri);
        $contextMock->method('generateState')->willReturn($expectedState);
        $contextMock->method('generateNonce')->willReturn($expectedNonce);

        /** @var ProConnectHttpClient&MockObject $httpClientMock */
        $httpClientMock = $this->createMock(ProConnectHttpClient::class);
        $httpClientMock
            ->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with(
                $expectedRedirectUri,
                $expectedState,
                $expectedNonce
            )
            ->willReturn($expectedAuthorizationUrl);

        /** @var ProConnectJwtValidator&MockObject $jwtValidator */
        $jwtValidator = $this->createMock(ProConnectJwtValidator::class);
        /** @var ProConnectJwtParser&MockObject $jwtParser */
        $jwtParser = $this->createMock(ProConnectJwtParser::class);
        $authentication = new ProConnectAuthentication(
            $httpClientMock,
            $contextMock,
            $jwtValidator,
            $jwtParser,
            'client_id',
            'client_secret'
        );

        $this->assertSame($expectedAuthorizationUrl, $authentication->getAuthorizationUrl());
    }

    private function createRequestStackWithSession(): RequestStack
    {
        $sessionData = ['proconnect_state' => 'valid_state', 'proconnect_nonce' => 'valid_nonce'];
        $session = new Session(new MockArraySessionStorage());
        foreach ($sessionData as $key => $value) {
            $session->set($key, $value);
        }

        $request = new Request();
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }
}
