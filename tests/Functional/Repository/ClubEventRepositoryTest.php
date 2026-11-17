<?php

namespace App\Tests\Functional\Repository;

use App\Dto\Request\CalendarEventRequest;
use App\Entity\ClubEvent;
use App\Entity\Enum\UserStatus;
use App\Repository\ClubEventRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ClubEventRepositoryTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
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
