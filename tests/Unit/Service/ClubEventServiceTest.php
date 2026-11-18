<?php

namespace App\Tests\Unit\Service;

use App\Repository\ClubEventRepository;
use App\Repository\UserRepository;
use App\Service\ClubEventService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;

class ClubEventServiceTest extends KernelTestCase
{
    protected function setUp(): void
    {
        $kernel = self::bootKernel();
    }

    public function testGetNextClubEventForUserRT(): void
    {
        $container = static::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable('2026-01-01'));
        $container->set(ClockInterface::class, $mockClock);

        $clubEventRepository = $container->get(ClubEventRepository::class);
        $userRepository = $container->get(UserRepository::class);

        $user = $userRepository->findOneByEmail('admin-territoire-13-01@signal-logement.fr');

        $service = new ClubEventService($clubEventRepository);
        $nextClubEvent = $service->getNextClubEventForUser($user);
        $this->assertNotNull($nextClubEvent);
    }

    public function testGetNextClubEventForUserRTWithoutResult(): void
    {
        $container = static::getContainer();
        $mockClock = new MockClock((new \DateTimeImmutable())->modify('+3 days'));
        $container->set(ClockInterface::class, $mockClock);

        $clubEventRepository = $container->get(ClubEventRepository::class);
        $userRepository = $container->get(UserRepository::class);

        $user = $userRepository->findOneByEmail('admin-territoire-13-01@signal-logement.fr');

        $service = new ClubEventService($clubEventRepository);
        $nextClubEvent = $service->getNextClubEventForUser($user);
        $this->assertNull($nextClubEvent);
    }

    public function testGetNextClubEventForAgentEligible(): void
    {
        $container = static::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable('2026-01-01'));
        $container->set(ClockInterface::class, $mockClock);

        $clubEventRepository = $container->get(ClubEventRepository::class);
        $userRepository = $container->get(UserRepository::class);

        $user = $userRepository->findOneByEmail('user-13-05@signal-logement.fr');

        $service = new ClubEventService($clubEventRepository);
        $nextClubEvent = $service->getNextClubEventForUser($user);
        $this->assertNotNull($nextClubEvent);
    }

    public function testGetNextClubEventForAgentNonEligible(): void
    {
        $container = static::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable('2026-01-01'));
        $container->set(ClockInterface::class, $mockClock);

        $clubEventRepository = $container->get(ClubEventRepository::class);
        $userRepository = $container->get(UserRepository::class);

        $user = $userRepository->findOneByEmail('user-34-01@signal-logement.fr');

        $service = new ClubEventService($clubEventRepository);
        $nextClubEvent = $service->getNextClubEventForUser($user);
        $this->assertNull($nextClubEvent);
    }
}
