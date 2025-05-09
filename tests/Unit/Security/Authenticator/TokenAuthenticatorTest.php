<?php

namespace App\Tests\Unit\Security\Authenticator;

use App\Entity\ApiUserToken;
use App\Entity\User;
use App\Repository\ApiUserTokenRepository;
use App\Security\Authenticator\TokenAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\Translation\TranslatorInterface;

class TokenAuthenticatorTest extends TestCase
{
    private AuthenticatorInterface $authenticator;

    protected function setUp(): void
    {
        $apiUserTokenRepository = $this->createMock(ApiUserTokenRepository::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(function ($key) {
                $translations = [
                    'Invalid credentials.' => 'Identifiants invalides.',
                    'An authentication exception occurred.' => 'Une exception d\'authentification s\'est produite.',
                ];

                return $translations[$key] ?? $key;
            });

        $this->authenticator = new TokenAuthenticator($apiUserTokenRepository, $translator);
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
        $exception = new AuthenticationException('Le token est invalide.');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Une exception d\'authentification s\'est produite.', $data['error']);
        $this->assertSame('Le token est invalide.', $data['message']);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
}
