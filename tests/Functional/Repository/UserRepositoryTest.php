<?php

namespace App\Tests\Functional\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testFindInactiveUserWithNbAffectations(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);

        $users = $userRepository->findInactiveWithNbAffectationPending();

        $this->assertIsArray($users);
        foreach ($users as $user) {
            $this->assertArrayHasKey('email', $user);
            if (!empty($user['signalements'])) {
                $this->assertEquals($user['nb_signalements'], \count(explode(',', $user['signalements'])));
            }
        }
    }
}
