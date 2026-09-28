<?php

namespace App\Tests\Functional\Command;

use App\Entity\Enum\SignalementStatus;
use App\Repository\SignalementRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ArchiveSignalementCommandTest extends KernelTestCase
{
    private const string UUID = '00000000-0000-0000-2022-000000000002';

    private CommandTester $commandTester;
    private SignalementRepository $signalementRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find('app:archive-signalement'));
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
    }

    public function testArchiveSignalementWithSuccess(): void
    {
        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute(['uuid' => self::UUID, 'zip' => '01']);

        $this->commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('a bien été archivé', $this->commandTester->getDisplay());

        $signalement = $this->signalementRepository->findOneBy(['uuid' => self::UUID]);
        $this->assertSame(SignalementStatus::ARCHIVED, $signalement->getStatut());
    }

    public function testArchiveSignalementCancelled(): void
    {
        $this->commandTester->setInputs(['no']);
        $this->commandTester->execute(['uuid' => self::UUID, 'zip' => '01']);

        $this->commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Opération annulée', $this->commandTester->getDisplay());

        $signalement = $this->signalementRepository->findOneBy(['uuid' => self::UUID]);
        $this->assertSame(SignalementStatus::CLOSED, $signalement->getStatut());
    }

    public function testArchiveSignalementWithWrongZipFails(): void
    {
        $this->commandTester->execute(['uuid' => self::UUID, 'zip' => '13']);

        $this->assertSame(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('(territoire : 01)', $this->commandTester->getDisplay());

        $signalement = $this->signalementRepository->findOneBy(['uuid' => self::UUID]);
        $this->assertSame(SignalementStatus::CLOSED, $signalement->getStatut());
    }

    public function testArchiveSignalementWithActiveStatusFails(): void
    {
        $uuid = '00000000-0000-0000-2022-000000000001';
        $this->commandTester->execute(['uuid' => $uuid, 'zip' => '13']);

        $this->assertSame(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('est au statut ACTIVE', $this->commandTester->getDisplay());

        $signalement = $this->signalementRepository->findOneBy(['uuid' => $uuid]);
        $this->assertSame(SignalementStatus::ACTIVE, $signalement->getStatut());
    }

    public function testArchiveSignalementWithUnknownUuidFails(): void
    {
        $this->commandTester->execute(['uuid' => '00000000-0000-0000-0000-999999999999', 'zip' => '01']);

        $this->assertSame(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Aucun signalement trouvé', $this->commandTester->getDisplay());
    }
}
