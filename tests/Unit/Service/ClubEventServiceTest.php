<?php

namespace App\Tests\Unit\Service\InjonctionBailleur;

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
        $container = self::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable(date('01-01-2026')));
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
        $container = self::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable(date('01-06-2026')));
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
        $container = self::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable(date('01-01-2026')));
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
        $container = self::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable(date('01-01-2026')));
        $container->set(ClockInterface::class, $mockClock);

        $clubEventRepository = $container->get(ClubEventRepository::class);
        $userRepository = $container->get(UserRepository::class);

        $user = $userRepository->findOneByEmail('user-34-01@signal-logement.fr');

        $service = new ClubEventService($clubEventRepository);
        $nextClubEvent = $service->getNextClubEventForUser($user);
        $this->assertNull($nextClubEvent);
    }
}
