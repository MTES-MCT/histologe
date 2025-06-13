<?php

namespace App\Tests\Unit\Security\Provider;

use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Security\Provider\SignalementUserProvider;
use App\Security\User\SignalementUser;
use App\Tests\FixturesHelper;
use Doctrine\ORM\NonUniqueResultException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class SignalementUserProviderTest extends TestCase
{
    use FixturesHelper;

    private MockObject|SignalementRepository $signalementRepository;
    private MockObject|UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->signalementRepository = $this->createMock(SignalementRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function testLoadUserByIdentifierReturnsSignalementUser(): void
    {
        $signalement = $this->getSignalementLocataire();
        $mockUser = $this->createMock(User::class);
        $this->signalementRepository
            ->method('findOneByCodeForPublic')
            ->with('12345678')
            ->willReturn($signalement);

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'luc.martin@example.com'])
            ->willReturn($mockUser);

        $signalementRepository = new SignalementUserProvider($this->signalementRepository, $this->userRepository);
        $user = $signalementRepository->loadUserByIdentifier('12345678:occupant');

        $this->assertInstanceOf(SignalementUser::class, $user);
        $this->assertSame('12345678:occupant', $user->getUserIdentifier());
        $this->assertSame('luc.martin@example.com', $user->getEmail());
        $this->assertSame('occupant', $user->getType());
        $this->assertNull($user->getPassword());
        $this->assertNull($user->getSalt());
    }

    /**
     * @throws NonUniqueResultException
     */
    public function testLoadUserByIdentifierThrowsExceptionWhenNotFound(): void
    {
        $this->signalementRepository
            ->method('findOneByCodeForPublic')
            ->willReturn(null);

        $signalementUserProvider = new SignalementUserProvider($this->signalementRepository, $this->userRepository);

        $this->expectException(UserNotFoundException::class);
        $signalementUserProvider->loadUserByIdentifier('00000000:occupant');
    }

    /**
     * @throws NonUniqueResultException
     */
    public function testRefreshUserThrowsExceptionForInvalidClass(): void
    {
        $signalementUserProvider = new SignalementUserProvider($this->signalementRepository, $this->userRepository);
        $invalidUser = $this->createMock(UserInterface::class);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Instances de');

        $signalementUserProvider->refreshUser($invalidUser);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function testRefreshUserReturnsRefreshedUser(): void
    {
        $signalement = $this->getSignalementLocataire();

        $mockUserEntity = $this->createMock(User::class);

        $this->signalementRepository
            ->method('findOneByCodeForPublic')
            ->with('12345678')
            ->willReturn($signalement);

        $this->userRepository
            ->method('findOneBy')
            ->with(['email' => 'luc.martin@example.com'])
            ->willReturn($mockUserEntity);

        $signalementUserProvider = new SignalementUserProvider($this->signalementRepository, $this->userRepository);

        $signalementUser = new SignalementUser(
            '12345678:occupant',
            'luc.martin@example.com',
            $mockUserEntity
        );

        $refreshedUser = $signalementUserProvider->refreshUser($signalementUser);

        $this->assertInstanceOf(SignalementUser::class, $refreshedUser);
        $this->assertSame('12345678:occupant', $refreshedUser->getUserIdentifier());
    }

    public function testSupports(): void
    {
        $signalementUserProvider = new SignalementUserProvider($this->signalementRepository, $this->userRepository);

        self::assertTrue($signalementUserProvider->supportsClass(SignalementUser::class));
    }
}
