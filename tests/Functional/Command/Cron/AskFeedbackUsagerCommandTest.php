<?php

namespace App\Tests\Functional\Command\Cron;

use App\Command\Cron\AskFeedbackUsagerCommand;
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
        putenv('APP=test');
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:ask-feedback-usager');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--debug' => InputOption::VALUE_NONE]);
        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('9 signalement(s) pour lesquels une demande', $output);
        $this->assertStringContainsString('6 signalement(s) en première relance', $output);
        $this->assertStringContainsString('1 signalement(s) en 2è relance', $output);
        $this->assertStringContainsString('1 signalement(s) en 3è relance', $output);
        $this->assertStringContainsString('1 signalement(s) en phase “boucle”', $output);
        $this->assertEmailCount(0);
    }

    public function testDisplayMessageSuccessfully(): void
    {
        putenv('APP=test');
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $nbSuiviFeedback = self::getContainer()->get(SuiviRepository::class)->count(['category' => SuiviCategory::ASK_FEEDBACK_SENT]);
        $this->assertEquals(6, $nbSuiviFeedback);

        $command = $application->find('app:ask-feedback-usager');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('6 signalement(s) en première relance', $output);
        $this->assertStringContainsString('1 signalement(s) en 2è relance', $output);
        $this->assertStringContainsString('1 signalement(s) en 3è relance', $output);
        $this->assertStringContainsString('1 signalement(s) en phase “boucle”', $output);
        $this->assertEmailCount(12);

        // first email is loop relance
        $firstEmail = $this->getMailerMessages()[0];
        $this->assertEmailSubjectContains($firstEmail, 'faites le point sur votre problème de logement !');
        $this->assertEmailHtmlBodyContains($firstEmail, 'Merci d\'indiquer si vous souhaitez poursuivre ou arrêter la procédure en cliquant sur les boutons ci-dessous.');

        // second email is third relance
        $secondEmail = $this->getMailerMessages()[1];
        $this->assertEmailSubjectContains($secondEmail, 'faites le point sur votre problème de logement !');
        $this->assertEmailHtmlBodyContains($secondEmail, 'Merci d\'indiquer si vous souhaitez poursuivre ou arrêter la procédure en cliquant sur les boutons ci-dessous.');

        // third email is second relance
        $thirdEmail = $this->getMailerMessages()[2];
        $this->assertEmailSubjectContains($thirdEmail, 'faites le point sur votre problème de logement !');
        $this->assertEmailHtmlBodyContains($thirdEmail, 'Cliquez sur le bouton ci-dessous pour nous envoyer un message de mise à jour !');

        // last email is cron summary
        $lastEmail = $this->getMailerMessages()[11];
        $this->assertEmailSubjectContains($lastEmail, 'La tâche planifiée s\'est bien exécutée.');
        $this->assertEmailHtmlBodyContains($lastEmail, 'La tâche planifiée <strong>demande de feedback à l\'usager</strong> s\'est terminée avec succès.');
        $this->assertEmailHtmlBodyContains($lastEmail, '9 signalement(s) pour lesquels une demande de feedback a été envoyée à l\'usager répartis comme suit :');
        $this->assertEmailHtmlBodyContains($lastEmail, '6 '.AskFeedbackUsagerCommand::FIRST_RELANCE_LOG_MESSAGE);
        $this->assertEmailHtmlBodyContains($lastEmail, '1 '.AskFeedbackUsagerCommand::SECOND_RELANCE_LOG_MESSAGE);
        $this->assertEmailHtmlBodyContains($lastEmail, '1 '.AskFeedbackUsagerCommand::THIRD_RELANCE_LOG_MESSAGE);
        $this->assertEmailHtmlBodyContains($lastEmail, '1 '.AskFeedbackUsagerCommand::LOOP_LOG_MESSAGE);

        $nbSuiviFeedback = self::getContainer()->get(SuiviRepository::class)->count(['category' => SuiviCategory::ASK_FEEDBACK_SENT]);
        $this->assertEquals(15, $nbSuiviFeedback);
    }
}
