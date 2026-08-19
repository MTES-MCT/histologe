<?php

namespace App\Tests\Functional\Command\Cron;

use App\Command\Cron\AskFeedbackUsagerCommand;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use Doctrine\ORM\EntityManagerInterface;
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

        $nbSuiviFeedback = static::getContainer()->get(SuiviRepository::class)->count(['category' => SuiviCategory::ASK_FEEDBACK_SENT]);
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

        $nbSuiviFeedback = static::getContainer()->get(SuiviRepository::class)->count(['category' => SuiviCategory::ASK_FEEDBACK_SENT]);
        $this->assertEquals(15, $nbSuiviFeedback);
    }

    public function testGraduatedSignalementStaysInLoopAfterNewPublicSuivi(): void
    {
        putenv('APP=test');
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        // 2022-8 est déjà en phase boucle (3 ASK_FEEDBACK_SENT en fixture). Un nouveau suivi
        // public de plus de 90 jours doit le laisser en boucle, pas repartir en 1ère relance.
        $this->addSuiviToSignalement('2022-8', new \DateTimeImmutable('-95 days'));

        $commandTester = new CommandTester($application->find('app:ask-feedback-usager'));
        $commandTester->execute(['--debug' => InputOption::VALUE_NONE]);
        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('6 signalement(s) en première relance', $output);
        $this->assertStringContainsString('1 signalement(s) en phase “boucle”', $output);
    }

    public function testGraduatedSignalementWithRecentPublicSuiviIsNotYetEligible(): void
    {
        putenv('APP=test');
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        // Le nouveau suivi public a moins de 90 jours : le dossier ne doit apparaître
        // dans aucune des 4 relances tant que ce délai n'est pas écoulé.
        $this->addSuiviToSignalement('2022-8', new \DateTimeImmutable('-60 days'));

        $commandTester = new CommandTester($application->find('app:ask-feedback-usager'));
        $commandTester->execute(['--debug' => InputOption::VALUE_NONE]);
        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('8 signalement(s) pour lesquels une demande', $output);
        $this->assertStringContainsString('6 signalement(s) en première relance', $output);
        $this->assertStringContainsString('0 signalement(s) en phase “boucle”', $output);
    }

    private function addSuiviToSignalement(string $reference, \DateTimeImmutable $createdAt): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['reference' => $reference]);
        $this->assertNotNull($signalement);

        $suivi = (new Suivi())
            ->setSignalement($signalement)
            ->setDescription('')
            ->setCategory(SuiviCategory::MESSAGE_PARTNER)
            ->setType(SuiviCategory::getSuiviTypeForSuiviCategory(SuiviCategory::MESSAGE_PARTNER))
            ->setIsVisibleForUsager(true)
            ->setCreatedAt($createdAt);
        $entityManager->persist($suivi);
        $entityManager->flush();
    }
}
