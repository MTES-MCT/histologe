<?php

namespace App\Tests\Unit\Manager;

use App\Dto\CountSignalement;
use App\Dto\CountSuivi;
use App\Dto\CountUser;
use App\Entity\Enum\InterfacageType;
use App\Repository\AffectationRepository;
use App\Repository\JobEventRepository;
use App\Repository\SignalementRepository;
use App\Service\DashboardWidget\WidgetDataKpiBuilder;
use App\Service\DashboardWidget\WidgetDataManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WidgetDataManagerTest extends TestCase
{
    private WidgetDataManager $widgetDataManager;
    private SignalementRepository|MockObject $signalementRepositoryMock;
    private JobEventRepository|MockObject $jobEventRepositoryMock;
    private MockObject|AffectationRepository $affectationRepositoryMock;

    protected function setUp(): void
    {
        $this->signalementRepositoryMock = $this->createMock(SignalementRepository::class);
        $this->jobEventRepositoryMock = $this->createMock(JobEventRepository::class);
        $this->affectationRepositoryMock = $this->createMock(AffectationRepository::class);
        $widgetDataKpiBuilderMock = $this->createMock(WidgetDataKpiBuilder::class);

        $this->widgetDataManager = new WidgetDataManager(
            $this->signalementRepositoryMock,
            $this->jobEventRepositoryMock,
            $this->affectationRepositoryMock,
            $widgetDataKpiBuilderMock,
        );
    }

    public function testCountSignalementAcceptedNoSuivi()
    {
        $territories = [];
        $this->signalementRepositoryMock
            ->expects($this->once())
            ->method('countSignalementAcceptedNoSuivi')
            ->with($territories)
            ->willReturn([]);
        $this->assertEquals([], $this->widgetDataManager->countSignalementAcceptedNoSuivi($territories));
    }

    public function testGetCountSignalementsByTerritory()
    {
        $this->signalementRepositoryMock
            ->expects($this->once())
            ->method('countSignalementTerritory')
            ->willReturn([
                ['new' => 1, 'no_affected' => 2],
                ['new' => 3, 'no_affected' => 4],
            ]);
        $this->assertEquals([
            ['new' => 1, 'no_affected' => 2],
            ['new' => 3, 'no_affected' => 4],
        ], $this->widgetDataManager->countSignalementsByTerritory());
    }

    public function testCountAffectationPartner()
    {
        $territories = [];
        $this->affectationRepositoryMock
            ->expects($this->once())
            ->method('countAffectationPartner')
            ->with($territories)
            ->willReturn([
                ['waiting' => 1, 'refused' => 2],
                ['waiting' => 3, 'refused' => 4],
            ]);
        $this->assertEquals([
            ['waiting' => 1, 'refused' => 2],
            ['waiting' => 3, 'refused' => 4],
        ], $this->widgetDataManager->countAffectationPartner($territories));
    }

    public function testFindLastJobEventByType()
    {
        $this->jobEventRepositoryMock
            ->expects($this->once())
            ->method('findLastJobEventByInterfacageType')
            ->with(InterfacageType::ESABORA->value)
            ->willReturn([]);

        $this->assertEquals([], $this->widgetDataManager->findLastJobEventByInterfacageType(
            InterfacageType::ESABORA->value,
            ['period' => 5],
            [])
        );
    }

    public function testCountDataKpi()
    {
        $countDataKpi = $this->widgetDataManager->countDataKpi([]);
        $this->assertInstanceOf(CountSignalement::class, $countDataKpi->getCountSignalement());
        $this->assertInstanceOf(CountSuivi::class, $countDataKpi->getCountSuivi());
        $this->assertInstanceOf(CountUser::class, $countDataKpi->getCountUser());
    }
}
