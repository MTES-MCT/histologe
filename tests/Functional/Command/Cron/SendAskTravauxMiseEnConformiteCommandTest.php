<?php

namespace App\Tests\Functional\Command\Cron;

use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Tester\CommandTester;

class SendAskTravauxMiseEnConformiteCommandTest extends KernelTestCase
{
    /**
     * @throws \DateMalformedStringException
     */
    public function testDisplayMessageSuccessfully(): void
    {
        putenv('APP=test');
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $container = static::getContainer();
        $mockClock = new MockClock();
        $container->set(ClockInterface::class, $mockClock);

        $command = $application->find('app:send-ask-travaux-mise-en-conformite');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringStartsWith('[OK] 1 emails de demande d\'avancement des travaux envoyés pour 1 signalements.', trim($output));
        $this->assertEmailCount(2);

        $mockClock->modify('-1 days');

        $command = $application->find('app:send-ask-travaux-mise-en-conformite');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringStartsWith('[OK] 0 emails de demande d\'avancement des travaux envoyés pour 0 signalements.', trim($output));
        $this->assertEmailCount(2);

        // Exemple au 1er octobre (horloge au 30 sept suite au -1 day) :
        // Séparer les deux étapes évite que '-1 month +1 day' passe par le 30 puis 31 août (PHP évalue les mois en premier).
        // On revient d'abord au 1er oct (+1 day) puis on recule au 1er sept (-1 month).
        $mockClock->modify('+1 day');
        $mockClock->modify('-1 month');

        $command = $application->find('app:send-ask-travaux-mise-en-conformite');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringStartsWith('[OK] 1 emails de demande d\'avancement des travaux envoyés pour 1 signalements.', trim($output));
        $this->assertEmailCount(4);
    }
}
