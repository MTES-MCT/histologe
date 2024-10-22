<?php

namespace App\Tests\Unit\Security;

namespace App\Tests\Security;

use App\Entity\ApiUserToken;
use App\Entity\User;
use App\Repository\ApiUserTokenRepository;
use App\Security\TokenAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class TokenAuthenticatorTest extends TestCase
{
    private ApiUserTokenRepository $apiUserTokenRepository;
    private AuthenticatorInterface $authenticator;

    protected function setUp(): void
    {
        $this->apiUserTokenRepository = $this->createMock(ApiUserTokenRepository::class);
        $this->authenticator = new TokenAuthenticator($this->apiUserTokenRepository);
    }

    public function testSupports(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer valid_token');

        $this->assertTrue($this->authenticator->supports($request));

        $request->headers->remove('Authorization');
        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testAuthenticateWithValidToken(): void
    {
        $user = new User();
        $user->setRoles([User::ROLE_API_USER]);

        $apiUserToken = new ApiUserToken();
        $apiUserToken->setOwnedBy($user);

        $request = new Request();
        $request->headers->set('Authorization', 'Bearer valid_token');

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
        $this->assertInstanceOf(UserBadge::class, $passport->getBadge(UserBadge::class));
    }

    public function testAuthenticateWithInvalidToken(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'invalid_token');

        $this->expectException(AuthenticationException::class);
        $this->authenticator->authenticate($request);
    }

    public function testOnAuthenticationSuccess(): void
    {
        $request = new Request();
        $token = $this->createMock(TokenInterface::class);

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'api');
        $this->assertNull($response);
    }

    public function testOnAuthenticationFailure(): void
    {
        $request = new Request();
        $exception = new AuthenticationException('Invalid token');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);
        $data = json_decode($response->getContent(), true);
        $this->assertSame('An authentication exception occurred.', $data['error']);
        $this->assertSame('Invalid token', $data['message']);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
}
