<?php

namespace App\Tests\Unit\Service\DashboardTabPanel\Kpi;

use App\Entity\User;
use App\Repository\Query\Dashboard\DossiersQuery;
use App\Repository\Query\Dashboard\DossiersSansSuivisPartenaireQuery;
use App\Repository\Query\Dashboard\DossiersSuivisUsagerQuery;
use App\Repository\Query\Dashboard\DossiersUndeliverableEmailQuery;
use App\Repository\Query\Dashboard\NouveauxDossiersKpiQuery;
use App\Repository\Query\Dashboard\SignalementsSansAffectationAccepteeQuery;
use App\Service\DashboardTabPanel\Kpi\CountAfermer;
use App\Service\DashboardTabPanel\Kpi\CountDossiersMessagesUsagers;
use App\Service\DashboardTabPanel\Kpi\CountNouveauxDossiers;
use App\Service\DashboardTabPanel\Kpi\TabCountKpiCalculator;
use App\Service\DashboardTabPanel\TabQueryParameters;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class TabCountKpiCalculatorTest extends TestCase
{
    private MockObject&NouveauxDossiersKpiQuery $nouveauxDossiersKpiQuery;
    private MockObject&SignalementsSansAffectationAccepteeQuery $signalementsSansAffectationAccepteeQuery;
    private MockObject&DossiersSuivisUsagerQuery $dossiersSuivisUsagerQuery;
    private MockObject&DossiersQuery $dossiersQuery;
    private MockObject&DossiersSansSuivisPartenaireQuery $dossiersSansSuivisPartenaireQuery;
    private MockObject&DossiersUndeliverableEmailQuery $dossiersUndeliverableEmailQuery;
    private MockObject&Security $security;
    private TabCountKpiCalculator $calculator;

    protected function setUp(): void
    {
        $this->nouveauxDossiersKpiQuery = $this->createMock(NouveauxDossiersKpiQuery::class);
        $this->signalementsSansAffectationAccepteeQuery = $this->createMock(SignalementsSansAffectationAccepteeQuery::class);
        $this->dossiersSuivisUsagerQuery = $this->createMock(DossiersSuivisUsagerQuery::class);
        $this->dossiersQuery = $this->createMock(DossiersQuery::class);
        $this->dossiersSansSuivisPartenaireQuery = $this->createMock(DossiersSansSuivisPartenaireQuery::class);
        $this->dossiersUndeliverableEmailQuery = $this->createMock(DossiersUndeliverableEmailQuery::class);
        $this->security = $this->createMock(Security::class);

        $this->calculator = new TabCountKpiCalculator(
            $this->nouveauxDossiersKpiQuery,
            $this->signalementsSansAffectationAccepteeQuery,
            $this->dossiersSuivisUsagerQuery,
            $this->dossiersQuery,
            $this->dossiersSansSuivisPartenaireQuery,
            $this->dossiersUndeliverableEmailQuery,
            $this->security
        );
    }

    public function testCountNouveauxDossiersForAdminTerritory(): void
    {
        $user = new User();
        $territories = [1, 2];
        $count = new CountNouveauxDossiers(1, 2, 3, 4, 5);

        $this->security->method('isGranted')->with('ROLE_ADMIN_TERRITORY')->willReturn(true);
        $this->nouveauxDossiersKpiQuery->expects($this->once())
            ->method('countNouveauxDossiersKpi')
            ->with($territories)
            ->willReturn($count);

        $result = $this->calculator->countNouveauxDossiers($territories, $user);
        $this->assertSame($count, $result);
    }

    public function testCountNouveauxDossiersForNonAdmin(): void
    {
        $user = new User();
        $territories = [1, 2];
        $count = new CountNouveauxDossiers(1, 2, 3, 4, 5);

        $this->security->method('isGranted')->with('ROLE_ADMIN_TERRITORY')->willReturn(false);
        $this->nouveauxDossiersKpiQuery->expects($this->once())
            ->method('countNouveauxDossiersKpi')
            ->with($territories, $user)
            ->willReturn($count);

        $result = $this->calculator->countNouveauxDossiers($territories, $user);
        $this->assertSame($count, $result);
    }

    public function testCountDossiersAFermer(): void
    {
        $user = new User();
        $params = new TabQueryParameters();
        $count = new CountAfermer(1, 2, 3, 4);

        $this->dossiersQuery->expects($this->once())
            ->method('countAllDossiersAferme')
            ->with($user, $params)
            ->willReturn($count);

        $result = $this->calculator->countDossiersAFermer($user, $params);
        $this->assertSame($count, $result);
    }

    public function testCountDossiersMessagesUsagers(): void
    {
        $user = new User();
        $params = new TabQueryParameters();
        $count = new CountDossiersMessagesUsagers(1, 2, 3);

        $this->dossiersSuivisUsagerQuery->expects($this->once())
            ->method('countAllMessagesUsagers')
            ->with($user, $params)
            ->willReturn($count);

        $result = $this->calculator->countDossiersMessagesUsagers($user, $params);
        $this->assertSame($count, $result);
    }

    public function testCountDossiersAVerifier(): void
    {
        $user = new User();
        $params = new TabQueryParameters();

        $this->dossiersSansSuivisPartenaireQuery->expects($this->once())
            ->method('countSignalements')
            ->with($user, $params)
            ->willReturn(10);

        $this->signalementsSansAffectationAccepteeQuery->expects($this->once())
            ->method('countSignalements')
            ->with($user, $params)
            ->willReturn(5);

        $this->dossiersUndeliverableEmailQuery->expects($this->once())
            ->method('count')
            ->with($user, $params)
            ->willReturn(2);

        $result = $this->calculator->countDossiersAVerifier($user, $params);
        $this->assertSame(10, $result->countSignalementsSansSuiviPartenaireDepuis60Jours);
        $this->assertSame(5, $result->countSignalementsSansAffectationAcceptee);
        $this->assertSame(2, $result->countAdresseEmailAVerifier);
        $this->assertSame(17, $result->total());
    }
}
