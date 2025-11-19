<?php

namespace App\Tests\Functional\Command\Cron;

use App\Command\Cron\AskFeedbackUsagerCommand;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
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
        $this->assertStringContainsString('9 signalement(s) pour lesquels une demande de feedback sera envoyée à l\'usager répartis comme suit :', $output);
        $this->assertStringContainsString('1 signalement(s) en 3è relance', $output);
        $this->assertStringContainsString('1 signalement(s) en 2è relance', $output);
        $this->assertStringContainsString('7 signalement(s) en première relance', $output);
        $this->assertStringContainsString('0 signalement(s) en phase “boucle”', $output);
        $this->assertEmailCount(0);
    }

    public function testDisplayMessageSuccessfully(): void
    {
        putenv('APP=test');
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:ask-feedback-usager');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('7 signalement(s) en première relance', $output);
        $this->assertStringContainsString('1 signalement(s) en 2è relance', $output);
        $this->assertStringContainsString('1 signalement(s) en 3è relance', $output);
        $this->assertStringContainsString('0 signalement(s) en phase “boucle”', $output);
        $this->assertEmailCount(12);

        // first email is third relance
        $firstEmail = $this->getMailerMessages()[0];
        $this->assertEmailSubjectContains($firstEmail, 'faites le point sur votre problème de logement !');
        $this->assertEmailHtmlBodyContains($firstEmail, 'Merci d\'indiquer si vous souhaitez poursuivre ou arrêter la procédure en cliquant sur les boutons ci-dessous.');

        // second email is second relance
        $secondEmail = $this->getMailerMessages()[1];
        $this->assertEmailSubjectContains($secondEmail, 'faites le point sur votre problème de logement !');
        $this->assertEmailHtmlBodyContains($secondEmail, 'Cliquez sur le bouton ci-dessous pour nous envoyer un message de mise à jour !');

        // last email is cron summary
        $lastEmail = $this->getMailerMessages()[11];
        $this->assertEmailSubjectContains($lastEmail, 'La tâche planifiée s\'est bien exécutée.');
        $this->assertEmailHtmlBodyContains($lastEmail, 'La tâche planifiée <strong>demande de feedback à l\'usager</strong> s\'est terminée avec succès.');
        $this->assertEmailHtmlBodyContains($lastEmail, '9 signalement(s) pour lesquels une demande de feedback a été envoyée à l\'usager répartis comme suit :');
        $this->assertEmailHtmlBodyContains($lastEmail, '7 '.AskFeedbackUsagerCommand::FIRST_RELANCE_LOG_MESSAGE);
        $this->assertEmailHtmlBodyContains($lastEmail, '1 '.AskFeedbackUsagerCommand::SECOND_RELANCE_LOG_MESSAGE);
        $this->assertEmailHtmlBodyContains($lastEmail, '1 '.AskFeedbackUsagerCommand::THIRD_RELANCE_LOG_MESSAGE);
        $this->assertEmailHtmlBodyContains($lastEmail, '0 '.AskFeedbackUsagerCommand::LOOP_LOG_MESSAGE);

        $nbSuiviFeedback = self::getContainer()->get(SuiviRepository::class)->count(['category' => SuiviCategory::ASK_FEEDBACK_SENT]);
        $this->assertEquals(12, $nbSuiviFeedback);
    }

    public function testWithASignalementInLoopRelance(): void
    {
        putenv('APP=test');
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $suiviRepository = self::getContainer()->get(SuiviRepository::class);

        // Get a signalement already having 2 relances (to be able to go to 3rd relance and then loop)
        $signalements = $suiviRepository->findSignalementsForThirdAskFeedbackRelance(0);
        $this->assertNotEmpty($signalements, 'Aucun signalement disponible pour tester la boucle.');
        $signalementId = $signalements[0];
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalementId);
        $this->assertNotNull($signalement);

        $suivis = $suiviRepository->findBy([
            'signalement' => $signalement,
            'category' => SuiviCategory::ASK_FEEDBACK_SENT,
        ]);
        $this->assertGreaterThanOrEqual(2, count($suivis));

        // Create a new suivi ASK_FEEDBACK_SENT older than 90 days to trigger the loop
        $newSuivi = new Suivi();
        $newSuivi->setSignalement($signalement);
        $newSuivi->setCategory(SuiviCategory::ASK_FEEDBACK_SENT);
        $newSuivi->setCreatedAt(new \DateTimeImmutable('-95 days'));
        $newSuivi->setDescription('Suivi technique supplémentaire pour test boucle de relance.');
        $newSuivi->setType(Suivi::TYPE_TECHNICAL);
        $entityManager->persist($newSuivi);
        $entityManager->flush();
        $suivis = $suiviRepository->findBy([
            'signalement' => $signalement,
            'category' => SuiviCategory::ASK_FEEDBACK_SENT,
        ]);
        $this->assertGreaterThanOrEqual(3, count($suivis));

        // Run the command in debug mode first
        $command = $application->find('app:ask-feedback-usager');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--debug' => InputOption::VALUE_NONE]);
        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('9 signalement(s) pour lesquels une demande de feedback sera envoyée à l\'usager répartis comme suit :', $output);
        $this->assertStringContainsString('7 signalement(s) en première relance', $output);
        $this->assertStringContainsString('1 signalement(s) en 2è relance', $output);
        $this->assertStringContainsString('0 signalement(s) en 3è relance', $output);
        $this->assertStringContainsString('1 signalement(s) en phase “boucle”', $output);
        $this->assertEmailCount(0);

        // Then run the command normally
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('7 signalement(s) en première relance', $output);
        $this->assertStringContainsString('1 signalement(s) en 2è relance', $output);
        $this->assertStringContainsString('0 signalement(s) en 3è relance', $output);
        $this->assertStringContainsString('1 signalement(s) en phase “boucle”', $output);
        $this->assertEmailCount(12);

        // first email is loop relance
        $firstEmail = $this->getMailerMessages()[0];
        $this->assertEmailSubjectContains($firstEmail, 'faites le point sur votre problème de logement !');
        $this->assertEmailHtmlBodyContains($firstEmail, 'Merci d\'indiquer si vous souhaitez poursuivre ou arrêter la procédure en cliquant sur les boutons ci-dessous.');

        // second email is second relance
        $secondEmail = $this->getMailerMessages()[1];
        $this->assertEmailSubjectContains($secondEmail, 'faites le point sur votre problème de logement !');
        $this->assertEmailHtmlBodyContains($secondEmail, 'Cliquez sur le bouton ci-dessous pour nous envoyer un message de mise à jour !');

        // last email is cron summary
        $lastEmail = $this->getMailerMessages()[11];
        $this->assertEmailSubjectContains($lastEmail, 'La tâche planifiée s\'est bien exécutée.');
        $this->assertEmailHtmlBodyContains($lastEmail, 'La tâche planifiée <strong>demande de feedback à l\'usager</strong> s\'est terminée avec succès.');
        $this->assertEmailHtmlBodyContains($lastEmail, '9 signalement(s) pour lesquels une demande de feedback a été envoyée à l\'usager répartis comme suit :');
        $this->assertEmailHtmlBodyContains($lastEmail, '7 '.AskFeedbackUsagerCommand::FIRST_RELANCE_LOG_MESSAGE);
        $this->assertEmailHtmlBodyContains($lastEmail, '1 '.AskFeedbackUsagerCommand::SECOND_RELANCE_LOG_MESSAGE);
        $this->assertEmailHtmlBodyContains($lastEmail, '0 '.AskFeedbackUsagerCommand::THIRD_RELANCE_LOG_MESSAGE);
        $this->assertEmailHtmlBodyContains($lastEmail, '1 '.AskFeedbackUsagerCommand::LOOP_LOG_MESSAGE);

        $newNbSuiviFeedback = $suiviRepository->count([
            'signalement' => $signalement,
            'category' => SuiviCategory::ASK_FEEDBACK_SENT,
        ]);
        $this->assertGreaterThanOrEqual(4, $newNbSuiviFeedback);
    }
}
