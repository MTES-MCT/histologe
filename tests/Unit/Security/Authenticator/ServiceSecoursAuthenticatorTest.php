<?php

namespace App\Tests\Unit\Security\Authenticator;

use App\Entity\ServiceSecoursRoute;
use App\Exception\ProConnect\ProConnectException;
use App\Exception\ServiceSecours\ServiceSecoursRouteResolutionException;
use App\Repository\ServiceSecoursRouteRepository;
use App\Security\Authenticator\ServiceSecoursAuthenticator;
use App\Security\User\ServiceSecoursUser;
use App\Service\Gouv\Insee\Exception\SireneRateLimitExceededException;
use App\Service\Gouv\ProConnect\Model\ProConnectUser;
use App\Service\Gouv\ProConnect\ProConnectAuthentication;
use App\Service\ServiceSecours\ServiceSecoursRouteResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Uid\Uuid;

class ServiceSecoursAuthenticatorTest extends TestCase
{
    private UrlGeneratorInterface&MockObject $urlGenerator;
    private ProConnectAuthentication&MockObject $proConnectAuthentication;
    private ServiceSecoursRouteResolver&MockObject $serviceSecoursRouteResolver;
    private ServiceSecoursRouteRepository&MockObject $serviceSecoursRouteRepository;
    private LoggerInterface&MockObject $logger;
    private ServiceSecoursAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->proConnectAuthentication = $this->createMock(ProConnectAuthentication::class);
        $this->serviceSecoursRouteResolver = $this->createMock(ServiceSecoursRouteResolver::class);
        $this->serviceSecoursRouteRepository = $this->createMock(ServiceSecoursRouteRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->authenticator = new ServiceSecoursAuthenticator(
            $this->urlGenerator,
            $this->proConnectAuthentication,
            $this->serviceSecoursRouteResolver,
            $this->serviceSecoursRouteRepository,
            $this->logger,
        );
    }

    public function testSupports(): void
    {
        $matchingRequest = Request::create('http://service-secours.localhost/proconnect/login-callback');
        $matchingRequest->attributes->set('_route', 'service_secours_proconnect_login_callback');

        $otherRequest = Request::create('http://service-secours.localhost/other');
        $otherRequest->attributes->set('_route', 'other_route');

        $this->assertTrue($this->authenticator->supports($matchingRequest));
        $this->assertFalse($this->authenticator->supports($otherRequest));
    }

    public function testAuthenticateSuccess(): void
    {
        $uuid = Uuid::v4();
        $request = Request::create('http://service-secours.localhost/proconnect/login-callback?code=fake_code&state=fake_state');

        $proConnectUser = new ProConnectUser([
            'uid' => '123',
            'sub' => 'sub_123',
            'email' => 'agent@sdis.fr',
            'siret' => '13000000000001',
            'organizational_unit' => 'SDIS 13',
            'chorusdt:matricule' => 'MAT-123',
            'given_name' => 'Jean',
            'usual_name' => 'Dupont',
        ]);

        $route = new ServiceSecoursRoute();
        $route->setUuid($uuid);
        $route->setName('sdis-13');

        $this->proConnectAuthentication
            ->expects($this->once())
            ->method('authenticateFromCallback')
            ->willReturn($proConnectUser);

        $this->serviceSecoursRouteResolver
            ->expects($this->once())
            ->method('resolve')
            ->with($proConnectUser)
            ->willReturn($route);

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
        $user = $passport->getUser();
        $this->assertInstanceOf(ServiceSecoursUser::class, $user);
        $this->assertSame('agent@sdis.fr:'.(string) $uuid, $user->getUserIdentifier());
        $this->assertSame('agent@sdis.fr', $user->getEmail());
        $this->assertSame((string) $uuid, $user->getServiceSecoursRouteUuid());
        $this->assertSame('MAT-123', $user->getMatricule());
        $this->assertSame('Jean Dupont', $user->getFullname());
    }

    public function testAuthenticateThrowsOnSireneRateLimit(): void
    {
        $request = Request::create('http://service-secours.localhost/proconnect/login-callback?code=fake_code&state=fake_state');

        $this->proConnectAuthentication
            ->expects($this->once())
            ->method('authenticateFromCallback')
            ->willThrowException(new SireneRateLimitExceededException('Rate limit'));

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage(ServiceSecoursAuthenticator::MESSAGE_SERVICE_INDISPONIBLE);

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsOnRouteResolutionError(): void
    {
        $request = Request::create('http://service-secours.localhost/proconnect/login-callback?code=fake_code&state=fake_state');

        $this->proConnectAuthentication
            ->expects($this->once())
            ->method('authenticateFromCallback')
            ->willThrowException(new ServiceSecoursRouteResolutionException('Route not found'));

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage(ServiceSecoursAuthenticator::MESSAGE_ROUTE_RESOLUTION_ERROR);

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsOnProConnectError(): void
    {
        $request = Request::create('http://service-secours.localhost/proconnect/login-callback?code=fake_code&state=fake_state');

        $this->proConnectAuthentication
            ->expects($this->once())
            ->method('authenticateFromCallback')
            ->willThrowException(new ProConnectException('ProConnect invalid'));

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage(ServiceSecoursAuthenticator::MESSAGE_PROCONNECT_ERROR);

        $this->authenticator->authenticate($request);
    }

    public function testOnAuthenticationSuccess(): void
    {
        $uuid = Uuid::v4();
        $request = Request::create('http://service-secours.localhost/proconnect/login-callback');

        $user = new ServiceSecoursUser(
            userIdentifier: 'agent@sdis.fr:'.(string) $uuid,
            email: 'agent@sdis.fr',
            serviceSecoursRouteUuid: (string) $uuid,
            matricule: 'MAT-123',
            fullname: 'Jean Dupont'
        );

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $route = new ServiceSecoursRoute();
        $route->setUuid($uuid);
        $route->setName('sdis-13');

        $this->serviceSecoursRouteRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => (string) $uuid])
            ->willReturn($route);

        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('service_secours_index', [
                'domain' => 'localhost',
                'slug' => 'sdis-13',
                'uuid' => $uuid,
            ])
            ->willReturn('http://service-secours.localhost/services-secours/sdis-13/'.(string) $uuid);

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'service_secours');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://service-secours.localhost/services-secours/sdis-13/'.(string) $uuid, $response->getTargetUrl());
    }

    public function testOnAuthenticationFailure(): void
    {
        $request = Request::create('http://service-secours.localhost/proconnect/login-callback');
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $exception = new CustomUserMessageAuthenticationException(ServiceSecoursAuthenticator::MESSAGE_PROCONNECT_ERROR);

        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('service_secours_home', ['domain' => 'localhost'])
            ->willReturn('http://service-secours.localhost');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://service-secours.localhost', $response->getTargetUrl());
        $flashes = $session->getFlashBag()->get('error error-raw');
        $this->assertNotEmpty($flashes);
        $this->assertStringContainsString(ServiceSecoursAuthenticator::MESSAGE_PROCONNECT_ERROR, $flashes[0]);
        $this->assertStringContainsString('http://localhost/signalement', $flashes[0]);
    }

    public function testIsInteractive(): void
    {
        $this->assertTrue($this->authenticator->isInteractive());
    }
}
