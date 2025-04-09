<?php

namespace App\Tests\Unit\Service\Gouv\ProConnect;

use App\Exception\ProConnect\ProConnectException;
use App\Service\Gouv\ProConnect\Model\ProConnectUser;
use App\Service\Gouv\ProConnect\ProConnectAuthentication;
use App\Service\Gouv\ProConnect\ProConnectContext;
use App\Service\Gouv\ProConnect\ProConnectHttpClient;
use App\Service\Gouv\ProConnect\ProConnectJwtParser;
use App\Service\Gouv\ProConnect\ProConnectJwtValidator;
use App\Service\Gouv\ProConnect\Response\JWKSResponse;
use App\Service\Gouv\ProConnect\Response\OAuth2TokenResponse;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;

class ProConnectAuthenticationTest extends KernelTestCase
{
    /**
     * @throws ProConnectException
     */
    public function testAuthenticateFromCallbackReturnsValidUser(): void
    {
        $context = new ProConnectContext($this->createRequestStackWithSession(), 'wiremock');

        $httpClient = $this->createMock(ProConnectHttpClient::class);
        $jwtValidator = $this->createMock(ProConnectJwtValidator::class);
        $jwtParser = $this->createMock(ProConnectJwtParser::class);

        $httpClient
            ->expects(self::once())
            ->method('requestToken')
            ->willReturn(new OAuth2TokenResponse(['access_token' => 'dummy', 'id_token' => 'dummy']));

        $jwksFile = file_get_contents(__DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/jwks.json');
        $httpClient
            ->expects(self::once())
            ->method('getJWKS')
            ->willReturn(new JWKSResponse($jwksFile));

        $jwtValidator
            ->expects(self::once())
            ->method('validate')
            ->willReturn(true);

        $proConnectData = [
            'uid' => '1234',
            'email' => 'proconnect@signal-logement.fr',
            'given_name' => 'Proconnect',
            'usual_name' => 'Proconnect',
        ];
        $httpClient
            ->expects(self::once())
            ->method('getUserDataJwt')
            ->willReturn(json_encode($proConnectData));

        $jwtParser->expects(self::once())->method('parse')
            ->willReturn($proConnectData);

        $proConnectAuthentication = new ProConnectAuthentication(
            $httpClient,
            $context,
            $jwtValidator,
            $jwtParser,
            $this->createMock(RouterInterface::class),
            'client_id',
            'client_secret',
        );

        $params = ['state' => 'valid_state', 'code' => 'valid_code'];

        /** @noinspection PhpUnhandledExceptionInspection */
        $user = $proConnectAuthentication->authenticateFromCallback($params);

        $this->assertInstanceOf(ProConnectUser::class, $user);
        $this->assertSame('1234', $user->uid);
        $this->assertSame('proconnect@signal-logement.fr', $user->email);
        $this->assertSame('Proconnect', $user->givenName);
        $this->assertSame('Proconnect', $user->usualName);
    }

    private function createRequestStackWithSession(): RequestStack
    {
        $sessionData = ['pr_state' => 'valid_state', 'pr_nonce' => 'valid_nonce'];
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
