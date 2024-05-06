<?php

namespace App\Tests\Functional\Repository;

use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private SignalementRepository $signalementRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
    }

    public function testFindInactiveUserWithNbAffectations(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);

        $users = $userRepository->findInactiveWithNbAffectationPending();

        $this->assertIsArray($users);
        $this->assertCount(8, $users);
        foreach ($users as $user) {
            $this->assertArrayHasKey('email', $user);
            if (!empty($user['signalements'])) {
                $this->assertEquals($user['nb_signalements'], \count(explode(',', $user['signalements'])));
            }
        }
    }

    public function testFindActiveTerritoryAdmins69(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000003']);

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);

        $users = $userRepository->findActiveTerritoryAdmins($signalement->getTerritory(), $signalement->getInseeOccupant());

        $this->assertIsArray($users);
        $this->assertCount(1, $users);
    }

    public function testFindActiveTerritoryAdmins13(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000006']);

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);

        $users = $userRepository->findActiveTerritoryAdmins($signalement->getTerritory(), $signalement->getInseeOccupant());

        $this->assertIsArray($users);
        $this->assertCount(2, $users);
    }
}
