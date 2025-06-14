<?php

namespace App\Tests\Functional\Command\Cron;

use App\Entity\Enum\SuiviCategory;
use App\Repository\SuiviRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Tester\CommandTester;

class AskFeedbackUsagerCommandTest extends KernelTestCase
{
    public function testDisplayMessageSuccessfullyForDebug(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:ask-feedback-usager');

        $commandTester = new CommandTester($command);

        $commandTester->execute(['--debug' => InputOption::VALUE_NONE]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('8 signalement(s) for which a request for feedback will be sent', $output);
        $this->assertEmailCount(0);
    }

    public function testDisplayMessageSuccessfully(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:ask-feedback-usager');

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('1 signalement(s) for which the two last suivis are technicals ', $output);
        $this->assertStringContainsString('1 signalement(s) for which the last suivi is technical', $output);
        $this->assertStringContainsString('6 signalement(s) without suivi public', $output);
        $this->assertEmailCount(11);

        $nbSuiviFeedback = self::getContainer()->get(SuiviRepository::class)->count(['category' => SuiviCategory::ASK_FEEDBACK_SENT]);
        $this->assertEquals(11, $nbSuiviFeedback);
    }
}
