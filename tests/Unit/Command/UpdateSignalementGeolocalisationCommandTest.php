<?php

namespace App\Tests\Unit\Command;

use App\Command\UpdateSignalementGeolocalisationCommand;
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
    private MockObject|SignalementRepository $signalementRepository;

    protected function setUp(): void
    {
        $this->addressService = $this->createMock(AddressService::class);
        $this->territoryRepository = $this->createMock(TerritoryRepository::class);
        $this->signalementRepository = $this->createMock(SignalementRepository::class);
    }

    public function testExecuteCommandWithoutArgumentAndOption(): void
    {
        $command = new UpdateSignalementGeolocalisationCommand(
            $this->addressService,
            $this->territoryRepository,
            $this->signalementRepository
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[WARNING] No address signalement to compute with BAN API', $output);
    }

    public function testExecuteCommandWithOptionUuid(): void
    {
        $this->signalementRepository
            ->expects($this->once())
            ->method('findBy')
            ->willReturn($this->getSignalementsWithoutGeolocation());

        $this->signalementRepository
            ->expects($this->atMost(2))
            ->method('save');

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
            $this->signalementRepository
        );

        $this->executeAssertOutputContainsAddress(
            $command,
            ['--uuid' => '00000000-0000-0000-2022-000000000001'],
            $address
        );
    }

    public function testExecuteCommandWithArgumentTerritory(): void
    {
        $this->signalementRepository
            ->expects($this->once())
            ->method('findWithNoGeolocalisation')
            ->willReturn($this->getSignalementsWithoutGeolocation(2));

        $this->signalementRepository
            ->expects($this->atMost(3))
            ->method('save');

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
            $this->signalementRepository
        );

        $this->executeAssertOutputContainsAddress(
            $command,
            ['--zip' => '13'],
            $address
        );
    }

    public function testExecuteCommandWithNoSignalement(): void
    {
        $this->signalementRepository
            ->expects($this->once())
            ->method('findWithNoGeolocalisation')
            ->willReturn([]);

        $this->signalementRepository
            ->expects($this->atMost(0))
            ->method('save');

        $command = new UpdateSignalementGeolocalisationCommand(
            $this->addressService,
            $this->territoryRepository,
            $this->signalementRepository
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
}
