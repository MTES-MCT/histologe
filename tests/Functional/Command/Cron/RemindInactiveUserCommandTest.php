<?php

namespace App\Tests\Functional\Command\Cron;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RemindInactiveUserCommandTest extends KernelTestCase
{
    public function testDisplayMessageSuccessfully(): void
    {
        putenv('APP=test');

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:remind-inactive-user');

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('10 users has been notified', $output);
        $this->assertEmailCount(11);
    }
}
