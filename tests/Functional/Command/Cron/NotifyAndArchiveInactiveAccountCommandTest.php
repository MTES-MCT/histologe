<?php

namespace App\Tests\Functional\Command\Cron;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class NotifyAndArchiveInactiveAccountCommandTest extends KernelTestCase
{
    public function testDisplayMessageSuccessfully(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

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

        $this->assertStringContainsString('2 first notifications sent to inactive users.', $output);
        $this->assertStringContainsString('0 second notifications sent to inactive users.', $output);
        $this->assertStringContainsString('0 accounts archived.', $output);
        $this->assertEmailCount(3);
    }
}
