<?php

namespace App\Tests\Functional\Command\Cron;

use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
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
        $this->assertStringContainsString('Aucun signalement n\'a été clôturé pour absence de suivi de travaux.', $output);
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
        $this->assertStringContainsString('Aucun signalement n\'a été clôturé pour absence de suivi de travaux.', $output);
        // On exécute le lendemain, aucun rappel ne doit être envoyé
        $mockClock->modify('+1 day');
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Aucun rappel n\'a été envoyé pour le suivi.', $output);
        $this->assertStringContainsString('Aucun rappel n\'a été envoyé pour les bailleurs.', $output);
        $this->assertStringContainsString('Aucun signalement n\'a été clôturé pour absence de suivi de travaux.', $output);
        // On exécute un mois plus tard, les rappels sont à nouveau envoyés (2e relance : toujours pas assez pour clôturer)
        $mockClock->modify('+1 month');
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('1 rappels faits pour les bailleurs pour des signalements avec suivi travaux.', $output);
        $this->assertStringContainsString('1 rappels faits pour les usagers pour des signalements avec suivi travaux.', $output);
        $this->assertStringContainsString('Aucun rappel n\'a été envoyé pour les bailleurs.', $output);
        $this->assertStringContainsString('Aucun signalement n\'a été clôturé pour absence de suivi de travaux.', $output);
        // Le lendemain, aucun rappel ne doit être envoyé
        $mockClock->modify('+1 day');
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Aucun rappel n\'a été envoyé pour le suivi.', $output);
        $this->assertStringContainsString('Aucun rappel n\'a été envoyé pour les bailleurs.', $output);
        $this->assertStringContainsString('Aucun signalement n\'a été clôturé pour absence de suivi de travaux.', $output);
    }

    public function testSignalementClosedAfterThreeRemindersWithoutActivity(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $container = static::getContainer();

        // Le MockClock doit être positionné avant tout flush() pour éviter l'initialisation de ClockInterface via EntityHistoryListener
        $now = new \DateTimeImmutable();
        $mockClock = new MockClock($now->modify('+4 months +1 day'));
        $container->set(ClockInterface::class, $mockClock);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000012']);
        $mailProprio = $signalement->getMailProprio();
        $mailOccupant = $signalement->getMailOccupant();

        // 3 relances déjà envoyées à chaque partie, espacées d'un mois, sans aucune réponse depuis
        foreach ([SuiviCategory::INJONCTION_BAILLEUR_REMINDER_FOR_BAILLEUR, SuiviCategory::INJONCTION_BAILLEUR_REMINDER_FOR_USAGER] as $category) {
            foreach ([1, 2, 3] as $monthsAfter) {
                $suivi = (new Suivi())
                    ->setSignalement($signalement)
                    ->setDescription('Relance de test')
                    ->setCategory($category)
                    ->setType(SuiviCategory::getSuiviTypeForSuiviCategory($category))
                    ->setCreatedAt($now->modify("+{$monthsAfter} months"));
                $entityManager->persist($suivi);
            }
        }
        $entityManager->flush();

        // On se place 1 mois après la 3e et dernière relance : le dossier doit être clôturé
        $command = $application->find('app:remind-injonction-signalement');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('1 signalement clôturé pour absence de suivi de travaux.', $output);
        $this->assertStringContainsString('Aucun rappel n\'a été envoyé pour le suivi', $output);

        // Le signalement est clôturé en base avec le bon motif
        $entityManager->clear();
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000012']);
        $this->assertSame(SignalementStatus::INJONCTION_CLOSED, $signalement->getStatut());
        $this->assertSame(MotifCloture::ABANDON_DE_PROCEDURE_ABSENCE_DE_REPONSE, $signalement->getMotifCloture());

        // Un suivi de clôture visible du bailleur et de l'usager a été créé
        $suivisCloture = $signalement->getSuivis()->filter(
            static fn (Suivi $suivi) => SuiviCategory::INJONCTION_BAILLEUR_CLOTURE_SANS_ACTIVITE === $suivi->getCategory()
        );
        $this->assertCount(1, $suivisCloture);

        // Il n'y a pas eu de suivi créé après ce suivi de cloture (pas de relance)
        $lastSuivi = $signalement->getSuivis()->last();
        $this->assertEquals($suivisCloture->first(), $lastSuivi);

        /** @var Suivi $suiviCloture */
        $suiviCloture = $suivisCloture->first();
        $this->assertTrue($suiviCloture->getIsVisibleForBailleur());
        $this->assertTrue($suiviCloture->getIsVisibleForUsager());

        // Le bailleur et l'usager sont notifiés par mail de la clôture (en copie cachée, comme les autres relances d'injonction)
        $closureMails = array_values(array_filter(
            self::getMailerMessages(),
            static fn ($mail) => $mail instanceof Email && str_contains($mail->getSubject() ?? '', 'Fin de la procédure concernant votre logement')
        ));
        $this->assertCount(2, $closureMails);
        $this->assertEmailAddressContains($closureMails[0], 'bcc', $mailProprio);
        $this->assertEmailAddressContains($closureMails[1], 'bcc', $mailOccupant);
    }

    public function testSignalementNotClosedUntilBothPartiesReachThreeUnansweredReminders(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $container = static::getContainer();

        // Le MockClock doit être positionné avant tout flush() pour éviter l'initialisation de ClockInterface via EntityHistoryListener
        $now = new \DateTimeImmutable();
        $mockClock = new MockClock($now->modify('+4 months +1 day'));
        $container->set(ClockInterface::class, $mockClock);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000012']);

        // 3 relances bailleur (dernière à +3 mois) et 3 relances usager, mais la 3e relance usager
        // n'intervient qu'à +5 mois (ex: l'usager a répondu une fois entre-temps, ce qui a repoussé sa propre série)
        foreach ([1, 2, 3] as $monthsAfter) {
            $entityManager->persist(
                (new Suivi())
                    ->setSignalement($signalement)
                    ->setDescription('Relance de test bailleur')
                    ->setCategory(SuiviCategory::INJONCTION_BAILLEUR_REMINDER_FOR_BAILLEUR)
                    ->setType(SuiviCategory::getSuiviTypeForSuiviCategory(SuiviCategory::INJONCTION_BAILLEUR_REMINDER_FOR_BAILLEUR))
                    ->setCreatedAt($now->modify("+{$monthsAfter} months"))
            );
        }
        foreach ([1, 2, 5] as $monthsAfter) {
            $entityManager->persist(
                (new Suivi())
                    ->setSignalement($signalement)
                    ->setDescription('Relance de test usager')
                    ->setCategory(SuiviCategory::INJONCTION_BAILLEUR_REMINDER_FOR_USAGER)
                    ->setType(SuiviCategory::getSuiviTypeForSuiviCategory(SuiviCategory::INJONCTION_BAILLEUR_REMINDER_FOR_USAGER))
                    ->setCreatedAt($now->modify("+{$monthsAfter} months"))
            );
        }
        $entityManager->flush();

        $command = $application->find('app:remind-injonction-signalement');
        $commandTester = new CommandTester($command);

        // À +4 mois : la 3e relance bailleur (+3 mois) est assez ancienne, mais pas la 3e relance usager (+5 mois)
        // → le dossier ne doit PAS être clôturé, il faut les deux parties
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString(
            'Aucun signalement n\'a été clôturé pour absence de suivi de travaux.',
            $commandTester->getDisplay()
        );
        $entityManager->clear();
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000012']);
        $this->assertSame(SignalementStatus::INJONCTION_BAILLEUR, $signalement->getStatut());

        // À +6 mois : la 3e relance usager (+5 mois) est à son tour assez ancienne → le dossier est clôturé
        $mockClock->modify('+2 months');
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString(
            '1 signalement clôturé pour absence de suivi de travaux.',
            $commandTester->getDisplay()
        );
        $entityManager->clear();
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000012']);
        $this->assertSame(SignalementStatus::INJONCTION_CLOSED, $signalement->getStatut());
    }

    public static function provideReminderSentData(): \Generator
    {
        // +2 par rapport au total historique : remindUsagerForCloture et remindUsagerForClotureAndClose
        // envoient chacun un mail de résumé admin (TYPE_CRON) à chaque exécution, même sans dossier concerné.
        yield 'One reminder, no suivi' => [
            '',
            'Aucun rappel n\'a été envoyé pour le suivi',
            '1 rappels faits pour des signalements sans réponse bailleur.',
            6,
        ];
        yield 'No reminder, no suivi' => [
            '-1 month',
            'Aucun rappel n\'a été envoyé pour le suivi',
            'Aucun rappel n\'a été envoyé pour les bailleurs.',
            5,
        ];
        yield 'One reminder, one suivi' => [
            '+1 month',
            '1 rappels faits pour les bailleurs pour des signalements avec suivi travaux.',
            '1 rappels faits pour des signalements sans réponse bailleur.',
            8,
        ];
    }
}
