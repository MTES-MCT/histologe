<?php

namespace App\Tests\Unit\Service;

use App\Dto\Request\CalendarEventRequest;
use App\Entity\ClubEvent;
use App\Entity\Enum\UserStatus;
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

    public function testFindAllFilteredForUserAndFindUsersToNotifyForClubEventConsistency(): void
    {
        $container = static::getContainer();
        $clubEventRepository = $container->get(ClubEventRepository::class);
        $userRepository = $container->get(UserRepository::class);

        $users = $userRepository->findBy(['statut' => UserStatus::ACTIVE->value]);
        $this->assertGreaterThan(0, count($users));
        $clubs = $clubEventRepository->findAll();
        $this->assertGreaterThan(0, count($clubs));

        // Événements auxquels chaque utilisateur est invité, toutes dates confondues
        $allDates = new CalendarEventRequest(dateDebut: '2000-01-01', dateFin: '2100-01-01');
        $eligibleClubIdsByUser = [];
        foreach ($users as $user) {
            $eligibleClubIdsByUser[$user->getId()] = array_map(
                static fn (ClubEvent $club): ?int => $club->getId(),
                $clubEventRepository->findAllFilteredForUser($user, $allDates)
            );
        }

        foreach ($clubs as $club) {
            $userEligibleForClub = $userRepository->findUsersToNotifyForClubEvent($club);
            foreach ($users as $user) {
                $isUserEligibleForClubEvent = in_array($club->getId(), $eligibleClubIdsByUser[$user->getId()], true);
                if ($user->getIsMailingClubEvent() && $isUserEligibleForClubEvent && !in_array($user, $userEligibleForClub, true)) {
                    $this->fail('User '.$user->getEmail().' is eligible for club event '.$club->getName().' but is not in the list of users to notify.');
                }
                if (!$isUserEligibleForClubEvent && in_array($user, $userEligibleForClub, true)) {
                    $this->fail('User '.$user->getEmail().' is not eligible for club event '.$club->getName().' but is in the list of users to notify.');
                }
            }
        }
    }
}
