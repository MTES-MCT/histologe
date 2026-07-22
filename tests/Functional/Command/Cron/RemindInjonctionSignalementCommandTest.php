<?php

namespace App\Tests\Functional\Command\Cron;

use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Repository\SignalementRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mime\Email;

class RemindInjonctionSignalementCommandTest extends KernelTestCase
{
    protected function setUp(): void
    {
        putenv('APP=test');
        putenv('COLUMNS=200');
    }

    #[DataProvider('provideReminderSentData')]
    public function testReminderSent(string $dateModifier, string $outputSuivi, string $outputReminderBailleurs, int $expectedEmailCount): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $container = static::getContainer();
        if (!empty($dateModifier)) {
            $mockClock = new MockClock(new \DateTimeImmutable($dateModifier));
            $container->set(ClockInterface::class, $mockClock);
        }

        $command = $application->find('app:remind-injonction-signalement');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($outputSuivi, $output);
        $this->assertStringContainsString($outputReminderBailleurs, $output);
        $this->assertEmailCount($expectedEmailCount);
    }

    public function testReminderSentAfterSecondRelance(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $container = static::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable('+1 months'));
        $container->set(ClockInterface::class, $mockClock);

        $command = $application->find('app:remind-injonction-signalement');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('1 rappels faits pour les bailleurs pour des signalements avec suivi travaux.', $output);
        $this->assertStringContainsString('1 rappels faits pour les usagers pour des signalements avec suivi travaux.', $output);
        $this->assertStringContainsString('1 rappels faits pour des signalements sans réponse bailleur.', $output);
        // On exécute le lendemain, aucun rappel ne doit être envoyé
        $mockClock->modify('+1 day');
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Aucun rappel n\'a été envoyé pour le suivi.', $output);
        $this->assertStringContainsString('Aucun rappel n\'a été envoyé pour les bailleurs.', $output);
        // On exécute un mois plus tard, les rappels sont à nouveau envoyés
        $mockClock->modify('+1 month');
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('1 rappels faits pour les bailleurs pour des signalements avec suivi travaux.', $output);
        $this->assertStringContainsString('1 rappels faits pour les usagers pour des signalements avec suivi travaux.', $output);
        $this->assertStringContainsString('Aucun rappel n\'a été envoyé pour les bailleurs.', $output);
        // Le lendemain, aucun rappel ne doit être envoyé
        $mockClock->modify('+1 day');
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Aucun rappel n\'a été envoyé pour le suivi.', $output);
        $this->assertStringContainsString('Aucun rappel n\'a été envoyé pour les bailleurs.', $output);
    }

    public function testReminderClotureBailleurThenAutoClose(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $container = static::getContainer();

        // MockClock doit être set avant flush() pour éviter l'initialisation de ClockInterface via EntityHistoryListener
        $mockClock = new MockClock(new \DateTimeImmutable());
        $container->set(ClockInterface::class, $mockClock);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000012']);

        // Le bailleur demande la clôture de l'injonction
        $suiviDemande = (new Suivi())
            ->setSignalement($signalement)
            ->setDescription('Les travaux sont terminés, merci de clôturer le signalement.')
            ->setCategory(SuiviCategory::INJONCTION_BAILLEUR_DEMANDE_CLOTURE_PAR_BAILLEUR)
            ->setType(SuiviCategory::getSuiviTypeForSuiviCategory(SuiviCategory::INJONCTION_BAILLEUR_DEMANDE_CLOTURE_PAR_BAILLEUR))
            ->setCreatedAt($mockClock->now());
        $entityManager->persist($suiviDemande);
        $entityManager->flush();

        $command = $application->find('app:remind-injonction-signalement');
        $commandTester = new CommandTester($command);

        // À j+16 après la demande de clôture, sans réponse de l'usager : relance envoyée (mail 318)
        $mockClock->modify('+16 days');
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString(
            '1 rappels envoyés à l\'usager suite à une demande de clôture par le bailleur au bout de 15 jours.',
            $commandTester->getDisplay()
        );
        $this->assertCount(1, $this->getMailerMessagesWithSubjectContaining('Votre bailleur indique la fin des travaux'));

        $entityManager->refresh($signalement);
        $suiviRelance = $entityManager->getRepository(Suivi::class)->findOneBy([
            'signalement' => $signalement,
            'category' => SuiviCategory::INJONCTION_BAILLEUR_RELANCE_USAGER_CLOTURE,
        ]);
        $this->assertStringContainsString('il y a 15 jours.', $suiviRelance->getDescription());

        // Le lendemain : la relance ne doit pas être renvoyée (non-régression)
        $mockClock->modify('+1 day');
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString(
            'Aucun rappel n\'a été envoyé pour l\'usager suite à une demande de clôture par le bailleur au bout de 15 jours.',
            $commandTester->getDisplay()
        );
        $this->assertCount(1, $this->getMailerMessagesWithSubjectContaining('Votre bailleur indique la fin des travaux'));

        // À j+31 après la demande, toujours sans réponse : le dossier est clôturé automatiquement,
        // malgré la relance de j+16 entre-temps
        $mockClock->modify('+14 days');
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString(
            '1 rappels envoyés à l\'usager et au bailleur suite à une demande de clôture par le bailleur au bout de 30 jours.',
            $commandTester->getDisplay()
        );
        // Exactement 2 mails "fin de procédure" (usager + bailleur), pas de doublon
        $this->assertCount(2, $this->getMailerMessagesWithSubjectContaining('Fin de la procédure concernant votre logement'));

        $entityManager->refresh($signalement);
        $this->assertSame(SignalementStatus::INJONCTION_CLOSED, $signalement->getStatut());
        $this->assertSame(MotifCloture::TRAVAUX_FAITS_OU_EN_COURS, $signalement->getMotifCloture());
    }

    /**
     * @return Email[]
     */
    private function getMailerMessagesWithSubjectContaining(string $needle): array
    {
        return array_values(array_filter(
            $this->getMailerMessages(),
            static fn ($message) => $message instanceof Email && str_contains((string) $message->getSubject(), $needle)
        ));
    }

    public static function provideReminderSentData(): \Generator
    {
        // +2 par rapport au total historique : remindUsagerForCloture et remindUsagerForClotureAndClose
        // envoient chacun un mail de résumé admin (TYPE_CRON) à chaque exécution, même sans dossier concerné.
        yield 'One reminder, no suivi' => [
            '',
            'Aucun rappel n\'a été envoyé pour le suivi',
            '1 rappels faits pour des signalements sans réponse bailleur.',
            5,
        ];
        yield 'No reminder, no suivi' => [
            '-1 month',
            'Aucun rappel n\'a été envoyé pour le suivi',
            'Aucun rappel n\'a été envoyé pour les bailleurs.',
            4,
        ];
        yield 'One reminder, one suivi' => [
            '+1 month',
            '1 rappels faits pour les bailleurs pour des signalements avec suivi travaux.',
            '1 rappels faits pour des signalements sans réponse bailleur.',
            7,
        ];
    }
}
