<?php

namespace App\Tests\Functional\Command\Cron;

use App\Entity\Suivi;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Tester\CommandTester;

class SendSuiviWaitingNotificationCommandTest extends KernelTestCase
{
    public function testDisplayMessageSuccessfully(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $container = self::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable('+30 minutes'));
        $container->set(ClockInterface::class, $mockClock);

        $command = $application->find('app:send-suivi-waiting-notification');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();

        $this->assertEquals('[OK] Les notifications de 6 suivis ont été envoyées avec succès.', trim($output));
        $this->assertEmailCount(10);

        $this->assertEquals(0, self::getContainer()->get('doctrine')->getManager()->getRepository(Suivi::class)->count(['waitingNotification' => 1]));
    }
}
