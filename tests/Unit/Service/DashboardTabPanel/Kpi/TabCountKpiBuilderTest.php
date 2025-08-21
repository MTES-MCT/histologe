<?php

namespace App\Tests\Service\DashboardTabPanel\Kpi;

use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Service\DashboardTabPanel\Kpi\CountAfermer;
use App\Service\DashboardTabPanel\Kpi\CountDossiersMessagesUsagers;
use App\Service\DashboardTabPanel\Kpi\CountNouveauxDossiers;
use App\Service\DashboardTabPanel\Kpi\TabCountKpi;
use App\Service\DashboardTabPanel\Kpi\TabCountKpiBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class TabCountKpiBuilderTest extends TestCase
{
    protected MockObject|SignalementRepository $signalementRepository;
    protected MockObject|SuiviRepository $suiviRepository;
    protected MockObject|Security $security;
    protected MockObject|TabCountKpiBuilder $tabCountKpiBuilder;

    protected function setUp(): void
    {
        $this->signalementRepository = $this->createMock(SignalementRepository::class);
        $this->suiviRepository = $this->createMock(SuiviRepository::class);
        $this->security = $this->createMock(Security::class);

        $this->tabCountKpiBuilder = new TabCountKpiBuilder(
            $this->signalementRepository,
            $this->suiviRepository,
            $this->security
        );
    }

    public function testWithTabCountKpiForAdminTerritory(): void
    {
        $user = new User();
        $territories = [1, 2];
        $territoryId = 1;
        $mesDossiers = '1';

        $this->security
            ->method('getUser')
            ->willReturn($user);
        $this->security
            ->method('isGranted')
            ->with('ROLE_ADMIN_TERRITORY')
            ->willReturn(true);

        $countNouveaux = new CountNouveauxDossiers(1, 2, 3, 4);
        $countMessages = new CountDossiersMessagesUsagers(1, 2, 3);
        $countAfermer = new CountAfermer(1, 1, 1);

        $this->signalementRepository
            ->expects($this->once())
            ->method('countNouveauxDossiersKpi')
            ->with($territories)
            ->willReturn($countNouveaux);

        $this->signalementRepository
        ->expects($this->once())
        ->method('countAllDossiersAferme')
        ->with($user, $territoryId)
        ->willReturn($countAfermer);

        $this->suiviRepository
            ->expects($this->once())
            ->method('countAllMessagesUsagers')
            ->with($user, $territoryId, $mesDossiers)
            ->willReturn($countMessages);

        $tabCountKpi = $this->tabCountKpiBuilder
            ->setTerritories($territories, $territoryId)
            ->setMesDossiers($mesDossiers, $mesDossiers)
            ->withTabCountKpi()
            ->build();

        $this->assertInstanceOf(TabCountKpi::class, $tabCountKpi);
        $this->assertSame(10, $tabCountKpi->countNouveauxDossiers);
        $this->assertSame(6, $tabCountKpi->countDossiersMessagesUsagers);
        $this->assertSame(3, $tabCountKpi->countDossiersAFermer);
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
        $countAfermer = new CountAfermer(1, 0, 0);

        $this->signalementRepository
            ->expects($this->once())
            ->method('countNouveauxDossiersKpi')
            ->with($territories, $user)
            ->willReturn($countNouveaux);

        $this->suiviRepository
            ->expects($this->once())
            ->method('countAllMessagesUsagers')
            ->with($user, $territoryId, $mesDossiers)
            ->willReturn($countMessages);

        $this->signalementRepository
        ->expects($this->once())
        ->method('countAllDossiersAferme')
        ->with($user, $territoryId)
        ->willReturn($countAfermer);

        $tabCountKpi = $this->tabCountKpiBuilder
            ->setTerritories($territories, $territoryId)
            ->setMesDossiers($mesDossiers, $mesDossiers)
            ->withTabCountKpi()
            ->build();

        $this->assertSame(6, $tabCountKpi->countNouveauxDossiers);
        $this->assertSame(5, $tabCountKpi->countDossiersMessagesUsagers);
        $this->assertSame(1, $tabCountKpi->countDossiersAFermer);
    }
}
