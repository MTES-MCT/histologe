<?php

namespace App\Tests\Functional\Command\Cron;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Tester\CommandTester;

class NotifyAndArchiveInactiveAccountCommandTest extends KernelTestCase
{
    public function testDisplayMessageSuccessfully(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $container = self::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable(date('Y-m-15')));
        $container->set(ClockInterface::class, $mockClock);

        $command = $application->find('app:notify-and-archive-inactive-accounts');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();

        $isActivated = $kernel->getContainer()->getParameter('feature_archive_inactive_account');
        if (!$isActivated) {
            $this->assertStringContainsString('Feature "FEATURE_ARCHIVE_INACTIVE_ACCOUNT" is disabled.', $output);

            return;
        }

        $this->assertStringContainsString('2 inactive accounts pending for archiving.', $output);
        $this->assertStringContainsString('0 accounts archived.', $output);
        $this->assertEmailCount(2);

        $mockClock->modify('+40 days'); // to ensure matching with fixtures data not based on the mocked clock

        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('3 accounts archived.', $output);
    }
}
