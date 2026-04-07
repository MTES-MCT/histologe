<?php

namespace App\Tests\Functional\Command\Cron;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SendDailyEmailsCommandTest extends KernelTestCase
{
    public function testDisplayMessageSuccessfully(): void
    {
        putenv('APP=test');
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:send-daily-emails');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();

        $explodedOutput = explode("\n", $output);
        $recapLine = $explodedOutput[count($explodedOutput) - 13];
        $this->assertStringContainsString(' emails récapitulatifs envoyés.', $recapLine);
        $nb = (int) str_replace(['[OK] ', ' emails récapitulatifs envoyés.'], '', $recapLine);
        ++$nb;
        $this->assertStringContainsString('15 emails de clubs envoyés à moins 7 jours.', $output); // 15 email + 1
        $this->assertStringContainsString('20 emails de clubs envoyés à moins 2 jours.', $output); // 20 email + 1
        $this->assertEmailCount($nb + 21 + 16);

        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $explodedOutput = explode("\n", $output);
        $recapLine = $explodedOutput[count($explodedOutput) - 13];
        $this->assertStringContainsString('0 emails récapitulatifs envoyés.', $recapLine);
        ++$nb;
        $this->assertStringContainsString('15 emails de clubs envoyés à moins 7 jours.', $output); // 15 email + 1
        $this->assertStringContainsString('20 emails de clubs envoyés à moins 2 jours.', $output); // 20 email + 1
        $this->assertEmailCount($nb + 21 + 16 + 21 + 16);
    }
}
