<?php

namespace App\Tests\Functional\Command\Cron;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Tester\CommandTester;

class RemindPendingDraftsCommandTest extends KernelTestCase
{
    /**
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     */
    public function testDisplayMessageSuccessfully(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();
        $sql = 'UPDATE signalement_draft SET created_at = \'2025-05-01 00:00:00\', updated_at = \'2025-05-01 00:00:00\' WHERE uuid LIKE :uuid';
        $connection->prepare($sql)->executeQuery(['uuid' => '00000000-0000-0000-2023-tierspart002']);

        $container = self::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable(date('2025-05-02')));
        $container->set(ClockInterface::class, $mockClock);

        $command = $application->find('app:remind-pending-drafts-bailleur-prevenu');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('0 usagers have been notified', $output);
        $this->assertEmailCount(1);

        $mockClock->modify('+3 months');

        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('1 usagers have been notified', $output);
        $this->assertEmailCount(3);
    }
}
