<?php

namespace App\Tests\Unit\Security;

use App\Entity\ApiUserToken;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\JsonLoginAuthenticator;
use PHPUnit\Framework\TestCase;
use Random\RandomException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class JsonLoginAuthenticatorTest extends TestCase
{
    private AuthenticatorInterface $authenticator;

    protected function setUp(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $this->authenticator = new JsonLoginAuthenticator($userRepository);
    }

    public function testSupports()
    {
        $request = Request::create('/api/login', 'POST', [], [], [], [], json_encode(['email' => 'user@example.com', 'password' => 'password']));
        $this->assertTrue($this->authenticator->supports($request));

        $request = Request::create('/api/login', 'POST', [], [], [], [], json_encode(['email' => 'user@example.com']));
        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testAuthenticateWithValidCredentials(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setRoles([User::ROLE_API_USER]);
        $user->setStatut(User::STATUS_ACTIVE);

        $request = Request::create('/api/login', 'POST', [], [], [], [], json_encode(['email' => 'user@example.com', 'password' => 'password']));
        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(Passport::class, $passport);
        $this->assertInstanceOf(UserBadge::class, $passport->getBadge(UserBadge::class));
        $this->assertInstanceOf(PasswordCredentials::class, $passport->getBadge(PasswordCredentials::class));
    }

    /**
     * @throws RandomException
     */
    public function testOnAuthenticationSuccess()
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setRoles([User::ROLE_API_USER]);
        $user->setStatut(User::STATUS_ACTIVE);

        $apiUserToken = new ApiUserToken();
        $user->addApiUserToken($apiUserToken);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')
            ->willReturn($user);

        $request = new Request();

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'api');
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(64, strlen($data['token']));
        $this->assertSame($apiUserToken->getExpiresAt()->format(\DATE_ATOM), $data['expires_at']);
    }

    public function testOnAuthenticationFailure()
    {
        $request = new Request();
        $exception = new AuthenticationException('Invalid credentials.');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);
        $data = json_decode($response->getContent(), true);
        $this->assertSame('An authentication exception occurred.', $data['error']);
        $this->assertSame('Invalid credentials.', $data['message']);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
}
