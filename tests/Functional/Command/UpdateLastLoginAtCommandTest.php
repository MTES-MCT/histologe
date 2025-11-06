<?php

namespace App\Tests\Functional\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateLastLoginAtCommandTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?CommandTester $commandTester;
    private User $user;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->entityManager = $entityManager;

        $application = new Application($kernel);
        $command = $application->find('app:user-update-last-login-at');
        $this->commandTester = new CommandTester($command);
        $this->user = new User();
        $this->user->setEmail('test-update@example.com');
        $this->user->setPassword('dummy');
        $this->user->setLastLoginAt(new \DateTimeImmutable('2025-05-09'));
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();

        $this->entityManager->getConnection()->insert('history_entry', [
            'user_id' => $this->user->getId(),
            'event' => 'LOGIN',
            'entity_name' => 'App\Entity\User',
            'created_at' => (new \DateTimeImmutable('2025-10-01'))->format('Y-m-d H:i:s'),
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testDryRunShowsUsersWithoutUpdating(): void
    {
        $oldDate = $this->user->getLastLoginAt();

        $this->commandTester->execute(['--dry-run' => true]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Mode dry-run', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $this->entityManager->refresh($this->user);
        $this->assertEquals($oldDate, $this->user->getLastLoginAt(), 'Aucune mise à jour en dry-run');
    }

    public function testCommandUpdatesUsersWhenNeeded(): void
    {
        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('utilisateurs mis à jour', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $this->entityManager->clear();
        $updatedUser = $this->entityManager->getRepository(User::class)->find($this->user->getId());

        $this->assertEquals(
            new \DateTimeImmutable('2025-10-01'),
            $updatedUser->getLastLoginAt(),
            'lastLoginAt doit être mis à jour avec la dernière date de LOGIN'
        );
    }
}
