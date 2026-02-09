<?php

namespace App\Tests\Functional\Command\Cron;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Tester\CommandTester;

class RemindInjonctionSignalementCommandTest extends KernelTestCase
{
    public function testOneReminderNoSuiviToSend(): void
    {
        putenv('APP=test');
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:remind-injonction-signalement');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('Aucun rappel n\'a été envoyé pour le suivi', $output);
        $this->assertStringContainsString('1 rappels ont été faits pour des signalements en injonction dont le bailleur n\'a pas encore répondu.', $output);
        $this->assertEmailCount(3);
    }

    public function testNoReminderSent(): void
    {
        putenv('APP=test');
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $container = self::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable('-1 month'));
        $container->set(ClockInterface::class, $mockClock);

        $command = $application->find('app:remind-injonction-signalement');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('Aucun rappel n\'a été envoyé pour le suivi', $output);
        $this->assertStringContainsString('Aucun rappel n\'a été envoyé pour les bailleurs.', $output);
        $this->assertEmailCount(2);
    }

    public function testReminderSent(): void
    {
        putenv('APP=test');
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $container = self::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable('+1 month'));
        $container->set(ClockInterface::class, $mockClock);

        $command = $application->find('app:remind-injonction-signalement');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('1 rappels ont été faits pour des signalements en injonction.', $output);
        $this->assertStringContainsString('1 rappels ont été faits pour des signalements en injonction dont le bailleur n\'a pas encore répondu.', $output);
        $this->assertEmailCount(5);
    }
}
