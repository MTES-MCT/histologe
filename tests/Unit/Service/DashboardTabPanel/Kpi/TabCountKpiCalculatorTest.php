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
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class TabCountKpiCalculatorTest extends TestCase
{
    private Stub&NouveauxDossiersKpiQuery $nouveauxDossiersKpiQuery;
    private Stub&SignalementsSansAffectationAccepteeQuery $signalementsSansAffectationAccepteeQuery;
    private Stub&DossiersSuivisUsagerQuery $dossiersSuivisUsagerQuery;
    private Stub&DossiersQuery $dossiersQuery;
    private Stub&DossiersSansSuivisPartenaireQuery $dossiersSansSuivisPartenaireQuery;
    private Stub&DossiersUndeliverableEmailQuery $dossiersUndeliverableEmailQuery;
    private Stub&Security $security;

    protected function setUp(): void
    {
        $this->nouveauxDossiersKpiQuery = $this->createStub(NouveauxDossiersKpiQuery::class);
        $this->signalementsSansAffectationAccepteeQuery = $this->createStub(SignalementsSansAffectationAccepteeQuery::class);
        $this->dossiersSuivisUsagerQuery = $this->createStub(DossiersSuivisUsagerQuery::class);
        $this->dossiersQuery = $this->createStub(DossiersQuery::class);
        $this->dossiersSansSuivisPartenaireQuery = $this->createStub(DossiersSansSuivisPartenaireQuery::class);
        $this->dossiersUndeliverableEmailQuery = $this->createStub(DossiersUndeliverableEmailQuery::class);
        $this->security = $this->createStub(Security::class);
    }

    private function getCalculator(): TabCountKpiCalculator
    {
        return new TabCountKpiCalculator(
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
        $params = new TabQueryParameters();
        $territories = [1, 2];
        $count = new CountNouveauxDossiers(1, 2, 3, 4, 5);

        $security = $this->createMock(Security::class);
        $security->expects($this->once())->method('isGranted')->with('ROLE_ADMIN_TERRITORY')->willReturn(true);
        $this->security = $security;
        $nouveauxDossiersKpiQuery = $this->createMock(NouveauxDossiersKpiQuery::class);
        $nouveauxDossiersKpiQuery->expects($this->once())
            ->method('countNouveauxDossiersKpi')
            ->with($territories)
            ->willReturn($count);
        $this->nouveauxDossiersKpiQuery = $nouveauxDossiersKpiQuery;
        $result = $this->getCalculator()->countNouveauxDossiers($territories, $user, $params);
        $this->assertSame($count, $result);
    }

    public function testCountNouveauxDossiersForNonAdmin(): void
    {
        $user = new User();
        $params = new TabQueryParameters();
        $territories = [1, 2];
        $count = new CountNouveauxDossiers(1, 2, 3, 4, 5);

        $security = $this->createMock(Security::class);
        $security->expects($this->once())->method('isGranted')->with('ROLE_ADMIN_TERRITORY')->willReturn(false);
        $this->security = $security;
        $nouveauxDossiersKpiQuery = $this->createMock(NouveauxDossiersKpiQuery::class);
        $nouveauxDossiersKpiQuery->expects($this->once())
            ->method('countNouveauxDossiersKpi')
            ->with($territories, $user)
            ->willReturn($count);
        $this->nouveauxDossiersKpiQuery = $nouveauxDossiersKpiQuery;

        $result = $this->getCalculator()->countNouveauxDossiers($territories, $user, $params);
        $this->assertSame($count, $result);
    }

    public function testCountDossiersAFermer(): void
    {
        $user = new User();
        $params = new TabQueryParameters();
        $count = new CountAfermer(1, 2, 3, 4);

        $dossiersQuery = $this->createMock(DossiersQuery::class);
        $dossiersQuery->expects($this->once())
            ->method('countAllDossiersAferme')
            ->with($user, $params)
            ->willReturn($count);
        $this->dossiersQuery = $dossiersQuery;

        $result = $this->getCalculator()->countDossiersAFermer($user, $params);
        $this->assertSame($count, $result);
    }

    public function testCountDossiersMessagesUsagers(): void
    {
        $user = new User();
        $params = new TabQueryParameters();
        $count = new CountDossiersMessagesUsagers(1, 2, 3);

        $dossiersSuivisUsagerQuery = $this->createMock(DossiersSuivisUsagerQuery::class);
        $dossiersSuivisUsagerQuery->expects($this->once())
            ->method('countAllMessagesUsagers')
            ->with($user, $params)
            ->willReturn($count);
        $this->dossiersSuivisUsagerQuery = $dossiersSuivisUsagerQuery;

        $result = $this->getCalculator()->countDossiersMessagesUsagers($user, $params);
        $this->assertSame($count, $result);
    }

    public function testCountDossiersAVerifier(): void
    {
        $user = new User();
        $params = new TabQueryParameters();

        $dossiersSansSuivisPartenaireQuery = $this->createMock(DossiersSansSuivisPartenaireQuery::class);
        $dossiersSansSuivisPartenaireQuery->expects($this->once())
            ->method('countSignalements')
            ->with($user, $params)
            ->willReturn(10);
        $this->dossiersSansSuivisPartenaireQuery = $dossiersSansSuivisPartenaireQuery;

        $signalementsSansAffectationAccepteeQuery = $this->createMock(SignalementsSansAffectationAccepteeQuery::class);
        $signalementsSansAffectationAccepteeQuery->expects($this->once())
            ->method('countSignalements')
            ->with($user, $params)
            ->willReturn(5);
        $this->signalementsSansAffectationAccepteeQuery = $signalementsSansAffectationAccepteeQuery;

        $dossiersUndeliverableEmailQuery = $this->createMock(DossiersUndeliverableEmailQuery::class);
        $dossiersUndeliverableEmailQuery->expects($this->once())
            ->method('count')
            ->with($user, $params)
            ->willReturn(2);
        $this->dossiersUndeliverableEmailQuery = $dossiersUndeliverableEmailQuery;

        $result = $this->getCalculator()->countDossiersAVerifier($user, $params);
        $this->assertSame(10, $result->countSignalementsSansSuiviPartenaireDepuis60Jours);
        $this->assertSame(5, $result->countSignalementsSansAffectationAcceptee);
        $this->assertSame(2, $result->countAdresseEmailAVerifier);
        $this->assertSame(17, $result->total());
    }
}
