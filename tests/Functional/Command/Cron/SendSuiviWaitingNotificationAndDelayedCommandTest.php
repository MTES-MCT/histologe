<?php

namespace App\Tests\Functional\Command\Cron;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Repository\UserRepository;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Tester\CommandTester;

class SendSuiviWaitingNotificationAndDelayedCommandTest extends KernelTestCase
{
    public function testDisplayMessageSuccessfully(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $container = static::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable('+30 minutes'));
        $container->set(ClockInterface::class, $mockClock);

        $command = $application->find('app:send-suivi-waiting-notification-and-delayed');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();

        $this->assertStringStartsWith('[OK] Les notifications de 7 suivis ont été envoyées avec succès.', trim($output));
        $this->assertEmailCount(12);

        $suiviRepository = static::getContainer()->get(SuiviRepository::class);

        $this->assertEquals(0, $suiviRepository->count(['waitingNotification' => 1]));

        $this->assertStringEndsWith('[OK] 2 suivis générés pour 5 suivi différés traités.', trim($output));

        $userRepository = $this->getContainer()->get(UserRepository::class);
        $signalementRepository = $this->getContainer()->get(SignalementRepository::class);

        $signalement2025_09 = $signalementRepository->findOneBy(['reference' => '2025-09']);
        $signalement2024_08 = $signalementRepository->findOneBy(['reference' => '2024-08']);
        $user2025_09 = $userRepository->findOneBy(['email' => 'm.assin@yopmail.com']);
        $user2024_08 = $userRepository->findOneBy(['email' => 'georges.brassens34300@yopmail.com']);
        $suivi2025_09 = $signalement2025_09->getSuivis()->last();
        $suivi2024_08 = $signalement2024_08->getSuivis()->last();

        $this->assertInstanceOf(Suivi::class, $suivi2025_09);
        $this->assertInstanceOf(Suivi::class, $suivi2024_08);

        $this->assertEquals($user2025_09, $suivi2025_09->getCreatedBy());
        $this->assertEquals($user2024_08, $suivi2024_08->getCreatedBy());
        $this->assertEquals(SuiviCategory::SIGNALEMENT_EDITED_FO, $suivi2025_09->getCategory());
        $this->assertEquals(SuiviCategory::SIGNALEMENT_EDITED_FO, $suivi2024_08->getCategory());
        $this->assertTrue($suivi2025_09->getIsVisibleForUsager());
        $this->assertTrue($suivi2024_08->getIsVisibleForUsager());
        $this->assertFalse($suivi2025_09->getIsVisibleForBailleur());
        $this->assertFalse($suivi2024_08->getIsVisibleForBailleur());

        $description2025_09 = $suivi2025_09->getDescription();
        $this->assertStringContainsString('Des modifications ont été apportées par '.$user2025_09->getNomComplet(true).'.', $description2025_09);
        $this->assertStringContainsString('Situation du foyer', $description2025_09);
        $this->assertStringContainsString('Date de naissance de l&#039;occupant : 10/06/1986', $description2025_09);
        $this->assertStringContainsString('Allocataire / Caisse d&#039;allocation : CAF', $description2025_09);
        $this->assertStringContainsString('Envoi d&#039;une invitation à un tiers par l&#039;usager', $description2025_09);
        $this->assertStringContainsString('Allocataire / Caisse d&#039;allocation : CAF', $description2025_09);
        $this->assertStringContainsString('Nom : Puche', $description2025_09);

        $description2024_08 = $suivi2024_08->getDescription();
        $this->assertStringContainsString('Des modifications ont été apportées par '.$user2024_08->getNomComplet(true).'.', $description2024_08);
        $this->assertStringContainsString('Coordonnées de l&#039;occupant', $description2024_08);
        $this->assertStringContainsString('Civilité : Monsieur', $description2024_08);
        $this->assertStringContainsString('Informations sur l&#039;assurance', $description2024_08);
        $this->assertStringContainsString('Assurance contactée : Oui', $description2024_08);
    }
}
