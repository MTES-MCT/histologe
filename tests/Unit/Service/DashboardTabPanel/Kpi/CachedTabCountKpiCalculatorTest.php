<?php

namespace App\Tests\Unit\Service\DashboardTabPanel\Kpi;

use App\Entity\User;
use App\Service\DashboardTabPanel\Kpi\CachedTabCountKpiCalculator;
use App\Service\DashboardTabPanel\Kpi\CountAfermer;
use App\Service\DashboardTabPanel\Kpi\CountDossiersAVerifier;
use App\Service\DashboardTabPanel\Kpi\CountDossiersMessagesUsagers;
use App\Service\DashboardTabPanel\Kpi\CountNouveauxDossiers;
use App\Service\DashboardTabPanel\Kpi\TabCountKpiCacheHelper;
use App\Service\DashboardTabPanel\Kpi\TabCountKpiCalculator;
use App\Service\DashboardTabPanel\TabQueryParameters;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CachedTabCountKpiCalculatorTest extends TestCase
{
    private MockObject&TabCountKpiCalculator $calculator;
    private MockObject&TabCountKpiCacheHelper $cacheHelper;
    private CachedTabCountKpiCalculator $cachedCalculator;

    protected function setUp(): void
    {
        $this->calculator = $this->createMock(TabCountKpiCalculator::class);
        $this->cacheHelper = $this->createMock(TabCountKpiCacheHelper::class);
        $this->cachedCalculator = new CachedTabCountKpiCalculator($this->calculator, $this->cacheHelper);
    }

    public function testCountNouveauxDossiers(): void
    {
        $user = new User();
        $params = new TabQueryParameters();
        $territories = [1, 2];
        $count = new CountNouveauxDossiers(1, 2, 3, 4);

        $this->cacheHelper->expects($this->once())
            ->method('getOrSet')
            ->with(TabCountKpiCacheHelper::NOUVEAUX_DOSSIERS, $user, $params, $this->callback(static fn ($callback) => is_callable($callback)))
            ->willReturnCallback(static fn ($name, $u, $p, $callback) => $callback());

        $this->calculator->expects($this->once())
            ->method('countNouveauxDossiers')
            ->with($territories, $user, $params)
            ->willReturn($count);

        $result = $this->cachedCalculator->countNouveauxDossiers($territories, $user, $params);
        $this->assertSame($count, $result);
    }

    public function testCountDossiersAFermer(): void
    {
        $user = new User();
        $params = new TabQueryParameters();
        $count = new CountAfermer(1, 1, 1, 1);

        $this->cacheHelper->expects($this->once())
            ->method('getOrSet')
            ->with(TabCountKpiCacheHelper::DOSSIERS_A_FERMER, $user, $params, $this->callback(static fn ($callback) => is_callable($callback)))
            ->willReturnCallback(static fn ($name, $u, $p, $callback) => $callback());

        $this->calculator->expects($this->once())
            ->method('countDossiersAFermer')
            ->with($user, $params)
            ->willReturn($count);

        $result = $this->cachedCalculator->countDossiersAFermer($user, $params);
        $this->assertSame($count, $result);
    }

    public function testCountDossiersMessagesUsagers(): void
    {
        $user = new User();
        $params = new TabQueryParameters();
        $count = new CountDossiersMessagesUsagers(1, 2, 3);

        $this->cacheHelper->expects($this->once())
            ->method('getOrSet')
            ->with(TabCountKpiCacheHelper::DOSSIERS_MESSAGES_USAGERS, $user, $params, $this->callback(static fn ($callback) => is_callable($callback)))
            ->willReturnCallback(static fn ($name, $u, $p, $callback) => $callback());

        $this->calculator->expects($this->once())
            ->method('countDossiersMessagesUsagers')
            ->with($user, $params)
            ->willReturn($count);

        $result = $this->cachedCalculator->countDossiersMessagesUsagers($user, $params);
        $this->assertSame($count, $result);
    }

    public function testCountDossiersAVerifier(): void
    {
        $user = new User();
        $params = new TabQueryParameters();
        $count = new CountDossiersAVerifier(3, 2, 9);

        $this->cacheHelper->expects($this->once())
            ->method('getOrSet')
            ->with(TabCountKpiCacheHelper::DOSSIERS_A_VERIFIER, $user, $params, $this->callback(static fn ($callback) => is_callable($callback)))
            ->willReturnCallback(static fn ($name, $u, $p, $callback) => $callback());

        $this->calculator->expects($this->once())
            ->method('countDossiersAVerifier')
            ->with($user, $params)
            ->willReturn($count);

        $result = $this->cachedCalculator->countDossiersAVerifier($user, $params);
        $this->assertSame($count, $result);
    }
}
