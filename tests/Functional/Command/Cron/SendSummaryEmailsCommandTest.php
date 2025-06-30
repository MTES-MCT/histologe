<?php

namespace App\Tests\Functional\Command\Cron;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SendSummaryEmailsCommandTest extends KernelTestCase
{
    public function testDisplayMessageSuccessfully(): void
    {
        putenv('APP=test');
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:send-summary-emails');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();

        $explodedOutput = explode("\n", $output);
        $lastLine = $explodedOutput[count($explodedOutput) - 3];
        $this->assertStringContainsString(' emails récapitulatifs envoyés.', $lastLine);
        $nb = (int) str_replace(['[OK] ', ' emails récapitulatifs envoyés.'], '', $lastLine);
        ++$nb;
        $this->assertEmailCount($nb);

        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $explodedOutput = explode("\n", $output);
        $lastLine = $explodedOutput[count($explodedOutput) - 3];
        $this->assertStringContainsString('0 emails récapitulatifs envoyés.', $lastLine);
        ++$nb;
        $this->assertEmailCount($nb);
    }
}
