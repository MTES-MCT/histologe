<?php

namespace App\Tests\Unit\Manager;

use App\Entity\JobEvent;
use App\Entity\Territory;
use App\Manager\WidgetDataManager;
use App\Repository\AffectationRepository;
use App\Repository\JobEventRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use PHPUnit\Framework\TestCase;

class WidgetDataManagerTest extends TestCase
{
    private WidgetDataManager $widgetDataManager;
    private $signalementRepositoryMock;
    private $jobEventRepositoryMock;
    private $affectationRepositoryMock;
    private $userRepositoryMock;

    protected function setUp(): void
    {
        $this->signalementRepositoryMock = $this->createMock(SignalementRepository::class);
        $this->jobEventRepositoryMock = $this->createMock(JobEventRepository::class);
        $this->affectationRepositoryMock = $this->createMock(AffectationRepository::class);
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->widgetDataManager = new WidgetDataManager(
            $this->signalementRepositoryMock,
            $this->jobEventRepositoryMock,
            $this->affectationRepositoryMock,
            $this->userRepositoryMock,
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
        ], $this->widgetDataManager->getCountSignalementsByTerritory());
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

        $this->assertEquals([], $this->widgetDataManager->findLastJobEventByType(JobEvent::TYPE_JOB_EVENT_ESABORA));
    }

    public function testCountDataKpi()
    {
        $countDataKpi = $this->widgetDataManager->countDataKpi();
        $this->assertArrayHasKey('count_signalement', $countDataKpi);
        $this->assertArrayHasKey('count_suivi', $countDataKpi);
        $this->assertArrayHasKey('count_user', $countDataKpi);
    }
}
