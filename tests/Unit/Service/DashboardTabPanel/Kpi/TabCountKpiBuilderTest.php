<?php

namespace App\Tests\Unit\Service\DashboardTabPanel\Kpi;

use App\Entity\User;
use App\Repository\Query\Dashboard\DossiersQuery;
use App\Repository\Query\Dashboard\DossiersSansSuivisPartenaireQuery;
use App\Repository\Query\Dashboard\DossiersSuivisUsagerQuery;
use App\Repository\Query\Dashboard\DossiersUndeliverableEmailQuery;
use App\Repository\Query\Dashboard\SignalementsSansAffectationAccepteeQuery;
use App\Repository\SignalementRepository;
use App\Service\DashboardTabPanel\Kpi\CountAfermer;
use App\Service\DashboardTabPanel\Kpi\CountDossiersAVerifier;
use App\Service\DashboardTabPanel\Kpi\CountDossiersMessagesUsagers;
use App\Service\DashboardTabPanel\Kpi\CountNouveauxDossiers;
use App\Service\DashboardTabPanel\Kpi\TabCountKpi;
use App\Service\DashboardTabPanel\Kpi\TabCountKpiBuilder;
use App\Service\DashboardTabPanel\Kpi\TabCountKpiCacheHelper;
use App\Service\DashboardTabPanel\TabQueryParameters;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class TabCountKpiBuilderTest extends TestCase
{
    protected MockObject&SignalementRepository $signalementRepository;
    protected MockObject&Security $security;
    protected TabCountKpiBuilder $tabCountKpiBuilder;
    protected MockObject&TabCountKpiCacheHelper $tabCountKpiCacheHelper;
    protected MockObject&SignalementsSansAffectationAccepteeQuery $signalementsSansAffectationAccepteeQuery;
    protected MockObject&DossiersQuery $dossiersQuery;
    protected MockObject&DossiersSuivisUsagerQuery $dossiersSuivisUsagerQuery;
    protected MockObject&DossiersSansSuivisPartenaireQuery $dossiersSansSuivisPartenaireQuery;
    protected MockObject&DossiersUndeliverableEmailQuery $dossiersUndeliverableEmailQuery;

    protected function setUp(): void
    {
        $this->signalementRepository = $this->createMock(SignalementRepository::class);
        $this->security = $this->createMock(Security::class);
        $this->dossiersQuery = $this->createMock(DossiersQuery::class);
        $this->dossiersSuivisUsagerQuery = $this->createMock(DossiersSuivisUsagerQuery::class);
        $this->dossiersSansSuivisPartenaireQuery = $this->createMock(DossiersSansSuivisPartenaireQuery::class);
        $this->dossiersUndeliverableEmailQuery = $this->createMock(DossiersUndeliverableEmailQuery::class);
        $this->tabCountKpiCacheHelper = $this->createMock(TabCountKpiCacheHelper::class);
        $this->signalementsSansAffectationAccepteeQuery = $this->createMock(SignalementsSansAffectationAccepteeQuery::class);

        $this->tabCountKpiBuilder = new TabCountKpiBuilder(
            $this->signalementRepository,
            $this->security,
            $this->tabCountKpiCacheHelper,
            $this->signalementsSansAffectationAccepteeQuery,
            $this->dossiersSuivisUsagerQuery,
            $this->dossiersQuery,
            $this->dossiersSansSuivisPartenaireQuery,
            $this->dossiersUndeliverableEmailQuery,
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
            mesDossiersAverifier: $mesDossiers,
            mesDossiersMessagesUsagers: $mesDossiers,
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

        $this->signalementRepository
            ->expects($this->never())
            ->method('countNouveauxDossiersKpi')
            ->with($territories)
            ->willReturn($countNouveaux);

        $this->dossiersQuery
            ->expects($this->never())
            ->method('countAllDossiersAferme')
            ->with($user, $params)
            ->willReturn($countAfermer);

        $this->dossiersSuivisUsagerQuery
            ->expects($this->never())
            ->method('countAllMessagesUsagers')
            ->with($user, $params)
            ->willReturn($countMessages);

        $this->dossiersSansSuivisPartenaireQuery
            ->expects($this->never())
            ->method('countSignalements')
            ->with($user, $params)
            ->willReturn(3);

        $this->tabCountKpiCacheHelper
            ->method('getOrSet')
            ->willReturnCallback(static function ($kpiName) use ($countNouveaux, $countAfermer, $countMessages, $countAverifier) {
                return match ($kpiName) {
                    TabCountKpiCacheHelper::NOUVEAUX_DOSSIERS => $countNouveaux,
                    TabCountKpiCacheHelper::DOSSIERS_A_FERMER => $countAfermer,
                    TabCountKpiCacheHelper::DOSSIERS_MESSAGES_USAGERS => $countMessages,
                    TabCountKpiCacheHelper::DOSSIERS_A_VERIFIER => $countAverifier,
                    default => null,
                };
            });

        $tabCountKpi = $this->tabCountKpiBuilder
            ->setTerritories($territories, $territoryId)
            ->setMesDossiers($mesDossiers, $mesDossiers, $mesDossiers)
            ->setSearchAverifier(null, [])
            ->withTabCountKpi()
            ->build();

        $this->assertInstanceOf(TabCountKpi::class, $tabCountKpi);
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
        $params = new TabQueryParameters(
            territoireId: $territoryId,
            partners: [],
            mesDossiersAverifier: $mesDossiers,
            mesDossiersMessagesUsagers: $mesDossiers,
            mesDossiersActiviteRecente: $mesDossiers,
        );

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

        $this->signalementRepository
            ->expects($this->never())
            ->method('countNouveauxDossiersKpi')
            ->with($territories, $user)
            ->willReturn($countNouveaux);

        $this->dossiersSuivisUsagerQuery
            ->expects($this->never())
            ->method('countAllMessagesUsagers')
            ->with($user, $params)
            ->willReturn($countMessages);

        $this->dossiersQuery
            ->expects($this->never())
            ->method('countAllDossiersAferme')
            ->with($user, $params)
            ->willReturn($countAfermer);

        $this->dossiersSansSuivisPartenaireQuery
            ->expects($this->never())
            ->method('countSignalements')
            ->with($user, $params)
            ->willReturn(2);

        $this->tabCountKpiCacheHelper
            ->method('getOrSet')
            ->willReturnCallback(static function ($kpiName) use ($countNouveaux, $countAfermer, $countMessages, $countAverifier) {
                return match ($kpiName) {
                    TabCountKpiCacheHelper::NOUVEAUX_DOSSIERS => $countNouveaux,
                    TabCountKpiCacheHelper::DOSSIERS_A_FERMER => $countAfermer,
                    TabCountKpiCacheHelper::DOSSIERS_MESSAGES_USAGERS => $countMessages,
                    TabCountKpiCacheHelper::DOSSIERS_A_VERIFIER => $countAverifier,
                    default => null,
                };
            });
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
