<?php

namespace App\Tests\Unit\Command;

use App\Command\UpdateSignalementGeolocalisationCommand;
use App\Manager\SignalementManager;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Service\DataGouv\AddressService;
use App\Service\DataGouv\Response\Address;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateSignalementGeolocalisationCommandTest extends TestCase
{
    use FixturesHelper;

    private MockObject|AddressService $addressService;
    private MockObject|TerritoryRepository $territoryRepository;
    private MockObject|SignalementManager $signalementManager;

    protected function setUp(): void
    {
        $this->addressService = $this->createMock(AddressService::class);
        $this->territoryRepository = $this->createMock(TerritoryRepository::class);
        $this->signalementManager = $this->createMock(SignalementManager::class);
    }

    public function testExecuteCommandWithoutArgumentAndOption(): void
    {
        $command = new UpdateSignalementGeolocalisationCommand(
            $this->addressService,
            $this->territoryRepository,
            $this->signalementManager
        );

        $signalementRepository = $this->createMockSignalementRepository(0);
        $this->signalementManager
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($signalementRepository);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[WARNING] No address signalement to compute with BAN API', $output);
    }

    public function testExecuteCommandWithOptionUuid(): void
    {
        $this->signalementManager
            ->expects($this->once())
            ->method('findBy')
            ->willReturn($this->getSignalementsWithoutGeolocation());

        $this->signalementManager
            ->expects($this->once())
            ->method('updateAddressOccupantFromAddress');

        $this->signalementManager
            ->expects($this->once())
            ->method('persist');

        $this->signalementManager
            ->expects($this->once())
            ->method('flush');

        $result = json_decode(
            file_get_contents(__DIR__.'/../../files/get_api_datagouv_ban_response.json'),
            true
        );

        $this->addressService
            ->expects($this->once())
            ->method('getAddress')
            ->willReturn($address = new Address($result));

        $command = new UpdateSignalementGeolocalisationCommand(
            $this->addressService,
            $this->territoryRepository,
            $this->signalementManager
        );

        $this->executeAssertOutputContainsAddress(
            $command,
            ['--uuid' => '00000000-0000-0000-2022-000000000001'],
            $address
        );
    }

    public function testExecuteCommandWithArgumentTerritory(): void
    {
        $this->signalementManager
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->createMockSignalementRepository(2));

        $this->signalementManager
            ->expects($this->atMost(2))
            ->method('updateAddressOccupantFromAddress');

        $this->signalementManager
            ->expects($this->atMost(2))
            ->method('persist');

        $this->signalementManager
            ->expects($this->once())
            ->method('flush');

        $result = json_decode(
            file_get_contents(__DIR__.'/../../files/get_api_datagouv_ban_response.json'),
            true
        );

        $this->addressService
            ->expects($this->atLeast(2))
            ->method('getAddress')
            ->willReturn($address = new Address($result));

        $command = new UpdateSignalementGeolocalisationCommand(
            $this->addressService,
            $this->territoryRepository,
            $this->signalementManager
        );

        $this->executeAssertOutputContainsAddress(
            $command,
            ['--zip' => '13'],
            $address
        );
    }

    public function testExecuteCommandWithNoSignalement(): void
    {
        $this->signalementManager
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->createMockSignalementRepository(0));

        $this->signalementManager
            ->expects($this->atMost(0))
            ->method('flush');

        $command = new UpdateSignalementGeolocalisationCommand(
            $this->addressService,
            $this->territoryRepository,
            $this->signalementManager
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute(['--zip' => '13']);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('No address signalement to compute with BAN API', $output);
    }

    private function executeAssertOutputContainsAddress(
        Command $command,
        array $option,
        Address $address,
    ): void {
        $commandTester = new CommandTester($command);
        $commandTester->execute($option);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($address->getLabel(), $output);
        $this->assertStringContainsString($address->getInseeCode(), $output);
        $this->assertStringContainsString($address->getLatitude(), $output);
        $this->assertStringContainsString($address->getLongitude(), $output);
    }

    private function createMockSignalementRepository(int $countSignalements = 0): MockObject|SignalementRepository
    {
        $signalementRepository = $this->createMock(SignalementRepository::class);
        $signalementRepository
            ->expects($this->once())
            ->method('findWithNoGeolocalisation')
            ->willReturn($countSignalements > 0 ? $this->getSignalementsWithoutGeolocation($countSignalements) : []);

        return $signalementRepository;
    }
}
