<?php

namespace App\Tests\Functional\Command\Cron;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SendSummaryEmailsCommandTest extends KernelTestCase
{
    public function testDisplayMessageSuccessfully(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:send-summary-emails');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();

        $isActivated = $kernel->getContainer()->getParameter('feature_email_recap');
        if (!$isActivated) {
            $this->assertStringContainsString('Feature "FEATURE_EMAIL_RECAP" is disabled.', $output);

            return;
        }

        $this->assertStringContainsString(' emails récapitulatifs envoyés.', $output);
        $nb = (int) str_replace(['[OK] ', ' emails récapitulatifs envoyés.'], '', $output);
        ++$nb;
        $this->assertEmailCount($nb);

        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('0 emails récapitulatifs envoyés.', $output);
        ++$nb;
        $this->assertEmailCount($nb);
    }
}
