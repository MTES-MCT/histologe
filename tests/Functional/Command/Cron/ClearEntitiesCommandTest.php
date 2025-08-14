<?php

declare(strict_types=1);

namespace App\Tests\Functional\Command\Cron;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ClearEntitiesCommandTest extends KernelTestCase
{
    public function testDisplayMessageSuccessfully(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:clear-entities');

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('JobEvent(s) deleted', $output);
        $this->assertStringContainsString('Notification(s) deleted', $output);
        $this->assertStringContainsString('SignalementDraft(s) deleted', $output);
        $this->assertStringContainsString('ApiUserToken(s) deleted', $output);
        $this->assertStringContainsString('HistoryEntry(s) deleted', $output);
        $this->assertEmailCount(5);
    }
}
