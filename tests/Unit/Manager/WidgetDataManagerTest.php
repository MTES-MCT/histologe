<?php

namespace App\Tests\Unit\Manager;

use App\Dto\CountSignalement;
use App\Dto\CountSuivi;
use App\Dto\CountUser;
use App\Entity\JobEvent;
use App\Entity\Territory;
use App\Repository\AffectationRepository;
use App\Repository\JobEventRepository;
use App\Repository\SignalementRepository;
use App\Service\DashboardWidget\WidgetDataKpiBuilder;
use App\Service\DashboardWidget\WidgetDataManager;
use PHPUnit\Framework\TestCase;

class WidgetDataManagerTest extends TestCase
{
    private WidgetDataManager $widgetDataManager;
    private $signalementRepositoryMock;
    private $jobEventRepositoryMock;
    private $affectationRepositoryMock;
    private $widgetDataKpiBuilderMock;

    protected function setUp(): void
    {
        $this->signalementRepositoryMock = $this->createMock(SignalementRepository::class);
        $this->jobEventRepositoryMock = $this->createMock(JobEventRepository::class);
        $this->affectationRepositoryMock = $this->createMock(AffectationRepository::class);
        $this->widgetDataKpiBuilderMock = $this->createMock(WidgetDataKpiBuilder::class);

        $this->widgetDataManager = new WidgetDataManager(
            $this->signalementRepositoryMock,
            $this->jobEventRepositoryMock,
            $this->affectationRepositoryMock,
            $this->widgetDataKpiBuilderMock,
        );
    }

    public function testCountSignalementAcceptedNoSuivi()
    {
        $territory = new Territory();
        $this->signalementRepositoryMock
            ->expects($this->once())
            ->method('countSignalementAcceptedNoSuivi')
            ->with($territory)
            ->willReturn([]);
        $this->assertEquals([], $this->widgetDataManager->countSignalementAcceptedNoSuivi($territory));
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
        $territory = new Territory();
        $this->affectationRepositoryMock
            ->expects($this->once())
            ->method('countAffectationPartner')
            ->with($territory)
            ->willReturn([
                ['waiting' => 1, 'refused' => 2],
                ['waiting' => 3, 'refused' => 4],
            ]);
        $this->assertEquals([
            ['waiting' => 1, 'refused' => 2],
            ['waiting' => 3, 'refused' => 4],
        ], $this->widgetDataManager->countAffectationPartner($territory));
    }

    public function testFindLastJobEventByType()
    {
        $this->jobEventRepositoryMock
            ->expects($this->once())
            ->method('findLastJobEventByType')
            ->with(JobEvent::TYPE_JOB_EVENT_ESABORA)
            ->willReturn([]);

        $this->assertEquals([], $this->widgetDataManager->findLastJobEventByType(
            JobEvent::TYPE_JOB_EVENT_ESABORA,
            5)
        );
    }

    public function testCountDataKpi()
    {
        $countDataKpi = $this->widgetDataManager->countDataKpi();
        $this->assertInstanceOf(CountSignalement::class, $countDataKpi->getCountSignalement());
        $this->assertInstanceOf(CountSuivi::class, $countDataKpi->getCountSuivi());
        $this->assertInstanceOf(CountUser::class, $countDataKpi->getCountUser());
    }
}
