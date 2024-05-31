<?php

namespace App\Tests\Functional\Command\Cron;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class AnonymizeExpiredAccountCommandTest extends KernelTestCase
{
    public function testDisplayMessageSuccessfully(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:anonymize-expired-account');

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();

        $isActivated = $kernel->getContainer()->getParameter('feature_anonymize_expired_account');
        if (!$isActivated) {
            $this->assertStringContainsString('Feature "FEATURE_ANONYMIZE_EXPIRED_ACCOUNT" is disabled.', $output);

            return;
        }
        $this->assertStringContainsString('1 expired users anonymized.', $output);
        $this->assertEmailCount(1);
    }
}
