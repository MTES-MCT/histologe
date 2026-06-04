<?php

namespace App\Tests\Unit\Service\DashboardTabPanel\Kpi;

use App\Entity\User;
use App\Service\DashboardTabPanel\Kpi\CountAfermer;
use App\Service\DashboardTabPanel\Kpi\CountDossiersAVerifier;
use App\Service\DashboardTabPanel\Kpi\CountDossiersMessagesUsagers;
use App\Service\DashboardTabPanel\Kpi\CountNouveauxDossiers;
use App\Service\DashboardTabPanel\Kpi\TabCountKpiBuilder;
use App\Service\DashboardTabPanel\Kpi\TabCountKpiCalculatorInterface;
use App\Service\DashboardTabPanel\TabQueryParameters;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class TabCountKpiBuilderTest extends TestCase
{
    protected MockObject&TabCountKpiCalculatorInterface $tabCountKpiCalculator;
    protected MockObject&Security $security;
    protected TabCountKpiBuilder $tabCountKpiBuilder;

    protected function setUp(): void
    {
        $this->tabCountKpiCalculator = $this->createMock(TabCountKpiCalculatorInterface::class);
        $this->security = $this->createMock(Security::class);

        $this->tabCountKpiBuilder = new TabCountKpiBuilder(
            $this->tabCountKpiCalculator,
            $this->security,
        );
    }

    public function testWithTabCountKpiForAdminTerritory(): void
    {
        $user = new User();
        $territories = [1, 2];
        $territoryId = 1;
        $mesDossiers = '1';
        $params = new TabQueryParameters(
            territoireId: $territoryId,
            partners: [],
            mesDossiersMessagesUsagers: $mesDossiers,
            mesDossiersAverifier: $mesDossiers,
            mesDossiersActiviteRecente: $mesDossiers,
        );

        $this->security
            ->method('getUser')
            ->willReturn($user);
        $this->security
            ->method('isGranted')
            ->with('ROLE_ADMIN_TERRITORY')
            ->willReturn(true);

        $countNouveaux = new CountNouveauxDossiers(1, 2, 3, 4);
        $countMessages = new CountDossiersMessagesUsagers(1, 2, 3);
        $countAfermer = new CountAfermer(1, 1, 1, 1);
        $countAverifier = new CountDossiersAVerifier(3, 2, 9);

        $this->tabCountKpiCalculator
            ->expects($this->once())
            ->method('countNouveauxDossiers')
            ->willReturn($countNouveaux);

        $this->tabCountKpiCalculator
            ->expects($this->once())
            ->method('countDossiersAFermer')
            ->willReturn($countAfermer);

        $this->tabCountKpiCalculator
            ->expects($this->once())
            ->method('countDossiersMessagesUsagers')
            ->willReturn($countMessages);

        $this->tabCountKpiCalculator
            ->expects($this->once())
            ->method('countDossiersAVerifier')
            ->willReturn($countAverifier);

        $tabCountKpi = $this->tabCountKpiBuilder
            ->setTerritories($territories, $territoryId)
            ->setMesDossiers($mesDossiers, $mesDossiers, $mesDossiers)
            ->setSearchAverifier(null, [])
            ->withTabCountKpi()
            ->build();

        $this->assertSame(10, $tabCountKpi->countNouveauxDossiers);
        $this->assertSame(6, $tabCountKpi->countDossiersMessagesUsagers);
        $this->assertSame(4, $tabCountKpi->countDossiersAFermer);
        $this->assertSame(14, $tabCountKpi->countDossiersAVerifier);
    }

    public function testWithTabCountKpiForNonAdmin(): void
    {
        $user = new User();
        $territories = [42];
        $territoryId = null;
        $mesDossiers = null;

        $this->security
            ->method('getUser')
            ->willReturn($user);
        $this->security
            ->method('isGranted')
            ->with('ROLE_ADMIN_TERRITORY')
            ->willReturn(false);

        $countNouveaux = new CountNouveauxDossiers(1, 2, 1, 2);
        $countMessages = new CountDossiersMessagesUsagers(1, 2, 2);
        $countAfermer = new CountAfermer(1, 0, 0, 2);
        $countAverifier = new CountDossiersAVerifier(2, 2);

        $this->tabCountKpiCalculator
            ->expects($this->once())
            ->method('countNouveauxDossiers')
            ->willReturn($countNouveaux);

        $this->tabCountKpiCalculator
            ->expects($this->once())
            ->method('countDossiersMessagesUsagers')
            ->willReturn($countMessages);

        $this->tabCountKpiCalculator
            ->expects($this->once())
            ->method('countDossiersAFermer')
            ->willReturn($countAfermer);

        $this->tabCountKpiCalculator
            ->expects($this->once())
            ->method('countDossiersAVerifier')
            ->willReturn($countAverifier);
        $tabCountKpi = $this->tabCountKpiBuilder
            ->setTerritories($territories, $territoryId)
            ->setMesDossiers($mesDossiers, $mesDossiers, $mesDossiers)
            ->setSearchAverifier(null, [])
            ->withTabCountKpi()
            ->build();

        $this->assertSame(6, $tabCountKpi->countNouveauxDossiers);
        $this->assertSame(5, $tabCountKpi->countDossiersMessagesUsagers);
        $this->assertSame(3, $tabCountKpi->countDossiersAFermer);
        $this->assertSame(4, $tabCountKpi->countDossiersAVerifier);
    }
}
