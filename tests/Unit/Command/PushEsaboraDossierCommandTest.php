<?php

namespace App\Tests\Unit\Command;

use App\Command\PushEsaboraDossierCommand;
use App\Entity\Enum\PartnerType;
use App\Entity\Territory;
use App\Messenger\InterconnectionBus;
use App\Repository\AffectationRepository;
use App\Repository\TerritoryRepository;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class PushEsaboraDossierCommandTest extends TestCase
{
    use FixturesHelper;
    private const ENV = 'dev';

    private MockObject|AffectationRepository $affectationRepository;
    private MockObject|TerritoryRepository $territoryRepository;

    private MockObject|InterconnectionBus $esaboraBus;

    protected function setUp(): void
    {
        $this->affectationRepository = $this->createMock(AffectationRepository::class);
        $this->territoryRepository = $this->createMock(TerritoryRepository::class);
        $this->esaboraBus = $this->createMock(InterconnectionBus::class);
        parent::setUp();
    }

    public function testExecuteWithZipOption(): void
    {
        $affectation1 = $this->getAffectation(PartnerType::ARS);
        $affectation2 = $this->getAffectation(PartnerType::ARS);

        $affectation1->setIsSynchronized(false);
        $affectation2->setIsSynchronized(false);

        $affectations = [
            ['affectation' => $affectation1, 'signalement_uuid' => $affectation1->getUuid()],
            ['affectation' => $affectation2, 'signalement_uuid' => $affectation2->getUuid()],
        ];

        $this->territoryRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['zip' => '01', 'isActive' => 1])
            ->willReturn($this->getTerritory());

        $this->affectationRepository
            ->expects($this->once())
            ->method('findAffectationSubscribedToEsabora')
            ->with(
                $this->equalTo(PartnerType::ARS),
                null,
                null,
                (new Territory())->setZip('01')->setIsActive(true)->setName('Ain')
            )
            ->willReturn($affectations);

        $this->affectationRepository
            ->expects($this->atMost(3))
            ->method('save');

        $this->esaboraBus
            ->expects($this->atMost(2))
            ->method('dispatch')
            ->withConsecutive([$affectation1], [$affectation2]);

        $command = new PushEsaboraDossierCommand(
            $this->affectationRepository,
            $this->territoryRepository,
            $this->esaboraBus,
            self::ENV
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'service_type' => 'sish',
            '--zip' => '01',
        ]);
    }

    public function testExecuteWithUuidOption(): void
    {
        $affectation = $this->getAffectation(PartnerType::ARS);
        $affectations = [
            ['affectation' => $affectation, 'signalement_uuid' => $affectation->getUuid()],
        ];

        $this->affectationRepository
            ->expects($this->once())
            ->method('findAffectationSubscribedToEsabora')
            ->with(
                $this->equalTo(PartnerType::ARS),
                $this->equalTo(false),
                $this->equalTo('00000000-0000-0000-2023-000000000010')
            )
            ->willReturn($affectations);

        $this->affectationRepository
            ->expects($this->atMost(2))
            ->method('save');

        $this->esaboraBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($affectation));

        $command = new PushEsaboraDossierCommand(
            $this->affectationRepository,
            $this->territoryRepository,
            $this->esaboraBus,
            self::ENV
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'service_type' => 'sish',
            '--uuid' => '00000000-0000-0000-2023-000000000010',
        ]);
    }
}
