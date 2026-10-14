<?php

namespace App\Tests\Unit\Security\Provider;

use App\Entity\ServiceSecoursRoute;
use App\Repository\ServiceSecoursRouteRepository;
use App\Security\Provider\ServiceSecoursUserProvider;
use App\Security\User\ServiceSecoursUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

class ServiceSecoursUserProviderTest extends TestCase
{
    public function testLoadUserByIdentifierSuccess(): void
    {
        $uuid = Uuid::v4();
        $repository = $this->createMock(ServiceSecoursRouteRepository::class);
        $route = new ServiceSecoursRoute();
        $route->setUuid($uuid);

        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => (string) $uuid])
            ->willReturn($route);

        $provider = new ServiceSecoursUserProvider($repository);
        $user = $provider->loadUserByIdentifier('agent@test.fr:'.(string) $uuid);

        $this->assertInstanceOf(ServiceSecoursUser::class, $user);
        $this->assertSame('agent@test.fr:'.(string) $uuid, $user->getUserIdentifier());
        $this->assertSame('agent@test.fr', $user->getEmail());
        $this->assertSame((string) $uuid, $user->getServiceSecoursRouteUuid());
        $this->assertSame(['ROLE_SERVICE_SECOURS'], $user->getRoles());
    }

    public function testLoadUserByIdentifierThrowsWhenInvalidFormat(): void
    {
        $repository = $this->createMock(ServiceSecoursRouteRepository::class);
        $provider = new ServiceSecoursUserProvider($repository);

        $this->expectException(UserNotFoundException::class);
        $provider->loadUserByIdentifier('invalid-identifier-without-separator');
    }

    public function testLoadUserByIdentifierThrowsWhenRouteNotFound(): void
    {
        $repository = $this->createMock(ServiceSecoursRouteRepository::class);
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => 'unknown-uuid'])
            ->willReturn(null);

        $provider = new ServiceSecoursUserProvider($repository);

        $this->expectException(UserNotFoundException::class);
        $provider->loadUserByIdentifier('agent@test.fr:unknown-uuid');
    }

    public function testRefreshUserSuccess(): void
    {
        $uuid = Uuid::v4();
        $repository = $this->createMock(ServiceSecoursRouteRepository::class);
        $route = new ServiceSecoursRoute();
        $route->setUuid($uuid);

        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => (string) $uuid])
            ->willReturn($route);

        $provider = new ServiceSecoursUserProvider($repository);
        $user = new ServiceSecoursUser(
            userIdentifier: 'agent@test.fr:'.(string) $uuid,
            email: 'agent@test.fr',
            serviceSecoursRouteUuid: (string) $uuid,
            matricule: 'MAT-123',
            fullname: 'Jean Dupont'
        );

        $refreshed = $provider->refreshUser($user);
        $this->assertSame($user, $refreshed);
        $this->assertSame('agent@test.fr:'.(string) $uuid, $refreshed->getUserIdentifier());
        $this->assertSame('MAT-123', $refreshed->getMatricule());
        $this->assertSame('Jean Dupont', $refreshed->getFullname());
    }

    public function testRefreshUserThrowsOnUnsupportedUser(): void
    {
        $repository = $this->createMock(ServiceSecoursRouteRepository::class);
        $provider = new ServiceSecoursUserProvider($repository);

        $unsupportedUser = $this->createMock(UserInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $provider->refreshUser($unsupportedUser);
    }

    public function testSupportsClass(): void
    {
        $repository = $this->createMock(ServiceSecoursRouteRepository::class);
        $provider = new ServiceSecoursUserProvider($repository);

        $this->assertTrue($provider->supportsClass(ServiceSecoursUser::class));
        $this->assertFalse($provider->supportsClass(\stdClass::class));
    }
}
