<?php

namespace App\Tests\Functional\Command\Cron;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class AskFeedbackUsagerCommandTest extends KernelTestCase
{
    public function testDisplayMessageSuccessfully(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:ask-feedback-usager');

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('1 signalement(s) with two last suivis are technicals and last one older than 30 days', $output);
        $this->assertStringContainsString('1 signalement(s) with last suivi technical and older than 30 days', $output);
        $this->assertStringContainsString('2 signalement(s) without suivi public from more than 45 days', $output);
        $this->assertEmailCount(5); // with cron notification email (4+1)
    }
}
