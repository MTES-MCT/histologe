<?php

namespace App\Tests\Functional\Service\Token;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Token\ActivationTokenGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ActivationTokenGeneratorTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
    }

    public function testValidateTokenActivation(): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'user-13-03@histologe.fr']);

        $container = static::getContainer();
        $activationTokenGenerator = $container->get(ActivationTokenGenerator::class);

        $this->assertEquals(
            $user,
            $activationTokenGenerator->validateToken($user, $user->getToken())
        );
    }

    public function testValidateTokenUpdatePassword(): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@histologe.fr']);

        $container = static::getContainer();
        $activationTokenGenerator = $container->get(ActivationTokenGenerator::class);

        $this->assertEquals(
            $user,
            $activationTokenGenerator->validateToken($user, $user->getToken())
        );
    }

    public function testValidateTokenNok(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findArchivedUserByEmail('user-01-07@histologe.fr');

        $container = static::getContainer();
        $activationTokenGenerator = $container->get(ActivationTokenGenerator::class);

        $this->assertFalse(
            $activationTokenGenerator->validateToken($user, $user->getToken())
        );
    }
}
