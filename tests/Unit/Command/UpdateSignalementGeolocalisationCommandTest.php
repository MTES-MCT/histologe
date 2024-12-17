<?php

namespace App\Tests\Unit\Command;

use App\Command\UpdateSignalementGeolocalisationCommand;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Service\Signalement\SignalementAddressUpdater;
use App\Tests\FixturesHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateSignalementGeolocalisationCommandTest extends TestCase
{
    use FixturesHelper;

    private MockObject|TerritoryRepository $territoryRepository;
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|SignalementAddressUpdater $signalementAddressUpdater;

    protected function setUp(): void
    {
        $this->territoryRepository = $this->createMock(TerritoryRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->signalementAddressUpdater = $this->createMock(SignalementAddressUpdater::class);
    }

    public function testExecuteCommandWithoutArgumentAndOption(): void
    {
        $command = new UpdateSignalementGeolocalisationCommand(
            $this->territoryRepository,
            $this->entityManager,
            $this->signalementAddressUpdater,
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[WARNING] No address signalement to compute with BAN API', $output);
    }

    public function testExecuteCommandWithOptionUuid(): void
    {
        $signalementRepository = $this->createMock(SignalementRepository::class);

        $signalementRepository
            ->expects($this->once())
            ->method('findBy')
            ->willReturn($this->getSignalements());

        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($signalementRepository);

        $this->signalementAddressUpdater
            ->expects($this->once())
            ->method('updateAddressOccupantFromBanData');

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('flush');

        $command = new UpdateSignalementGeolocalisationCommand(
            $this->territoryRepository,
            $this->entityManager,
            $this->signalementAddressUpdater
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute(['--uuid' => '00000000-0000-0000-2022-000000000001']);
        $commandTester->assertCommandIsSuccessful();
    }

    /**
     * @dataProvider provideTestCases
     */
    public function testExecuteCommandWith(string $providerMethod, array $option): void
    {
        $signalementRepository = $this->createMock(SignalementRepository::class);

        $this->createMockSignalementRepository($signalementRepository, 2, $providerMethod);

        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($signalementRepository);

        $this->signalementAddressUpdater
            ->expects($this->atMost(2))
            ->method('updateAddressOccupantFromBanData');

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('flush');

        $command = new UpdateSignalementGeolocalisationCommand(
            $this->territoryRepository,
            $this->entityManager,
            $this->signalementAddressUpdater
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute($option);
        $commandTester->assertCommandIsSuccessful();
    }

    public function provideTestCases(): \Generator
    {
        yield 'With territory option' => ['findWithNoGeolocalisation', ['--zip' => '13']];
        yield 'With date option' => ['findSignalementsBetweenDates', ['--from_created_at' => '2024-01-01']];
    }

    public function testExecuteCommandWithNoSignalement(): void
    {
        $signalementRepository = $this->createMock(SignalementRepository::class);

        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->createMockSignalementRepository($signalementRepository, 0));

        $this->entityManager
            ->expects($this->atMost(2))
            ->method('flush');

        $command = new UpdateSignalementGeolocalisationCommand(
            $this->territoryRepository,
            $this->entityManager,
            $this->signalementAddressUpdater
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute(['--zip' => '13']);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('No address signalement to compute with BAN API', $output);
    }

    private function createMockSignalementRepository(
        MockObject $signalementRepository,
        int $countSignalements = 0,
        $method = 'findWithNoGeolocalisation',
    ): MockObject|SignalementRepository {
        $signalementRepository
            ->expects($this->once())
            ->method($method)
            ->willReturn($countSignalements > 0 ? $this->getSignalements($countSignalements) : []);

        return $signalementRepository;
    }
}
