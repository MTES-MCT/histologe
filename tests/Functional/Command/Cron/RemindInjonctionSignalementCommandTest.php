<?php

namespace App\Tests\Functional\Command\Cron;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Tester\CommandTester;

class RemindInjonctionSignalementCommandTest extends KernelTestCase
{
    /**
     * @dataProvider provideReminderSentData
     */
    public function testReminderSent(string $dateModifier, string $outputSuivi, string $outputReminderBailleurs, int $expectedEmailCount): void
    {
        putenv('APP=test');

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $container = self::getContainer();
        if (!empty($dateModifier)) {
            $mockClock = new MockClock(new \DateTimeImmutable($dateModifier));
            $container->set(ClockInterface::class, $mockClock);
        }

        $command = $application->find('app:remind-injonction-signalement');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($outputSuivi, $output);
        $this->assertStringContainsString($outputReminderBailleurs, $output);
        $this->assertEmailCount($expectedEmailCount);
    }

    public function testReminderSentAfterSecondRelance(): void
    {
        putenv('APP=test');

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $container = self::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable('+1 months'));
        $container->set(ClockInterface::class, $mockClock);

        $command = $application->find('app:remind-injonction-signalement');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('1 rappels faits pour des signalements avec suivi travaux.', $output);
        $this->assertStringContainsString('1 rappels faits pour des signalements sans réponse bailleur.', $output);
        // On exécute le lendemain, aucun rappel ne doit être envoyé
        $mockClock->modify('+1 day');
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Aucun rappel n\'a été envoyé pour le suivi.', $output);
        $this->assertStringContainsString('Aucun rappel n\'a été envoyé pour les bailleurs.', $output);
        // On exécute un mois plus tard, les rappels sont à nouveau envoyés
        $mockClock->modify('+1 month');
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('1 rappels faits pour des signalements avec suivi travaux.', $output);
        $this->assertStringContainsString('Aucun rappel n\'a été envoyé pour les bailleurs.', $output);
        // Le lendemain, aucun rappel ne doit être envoyé
        $mockClock->modify('+1 day');
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Aucun rappel n\'a été envoyé pour le suivi.', $output);
        $this->assertStringContainsString('Aucun rappel n\'a été envoyé pour les bailleurs.', $output);
    }

    public function provideReminderSentData(): \Generator
    {
        yield 'One reminder, no suivi' => [
            '',
            'Aucun rappel n\'a été envoyé pour le suivi',
            '1 rappels faits pour des signalements sans réponse bailleur.',
            3,
        ];
        yield 'No reminder, no suivi' => [
            '-1 month',
            'Aucun rappel n\'a été envoyé pour le suivi',
            'Aucun rappel n\'a été envoyé pour les bailleurs.',
            2,
        ];
        yield 'One reminder, one suivi' => [
            '+1 month',
            '1 rappels faits pour des signalements avec suivi travaux.',
            '1 rappels faits pour des signalements sans réponse bailleur.',
            5,
        ];
    }
}
