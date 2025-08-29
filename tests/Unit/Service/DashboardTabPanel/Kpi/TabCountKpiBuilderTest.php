<?php

namespace App\Tests\Unit\Service\DashboardTabPanel\Kpi;

use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Service\DashboardTabPanel\Kpi\CountAfermer;
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
    protected MockObject|SignalementRepository $signalementRepository;
    protected MockObject|SuiviRepository $suiviRepository;
    protected MockObject|Security $security;
    protected MockObject|TabCountKpiBuilder $tabCountKpiBuilder;
    protected MockObject|TabCountKpiCacheHelper $tabCountKpiCacheHelper;

    protected function setUp(): void
    {
        $this->signalementRepository = $this->createMock(SignalementRepository::class);
        $this->suiviRepository = $this->createMock(SuiviRepository::class);
        $this->security = $this->createMock(Security::class);
        $this->tabCountKpiCacheHelper = $this->createMock(TabCountKpiCacheHelper::class);

        $this->tabCountKpiBuilder = new TabCountKpiBuilder(
            $this->signalementRepository,
            $this->suiviRepository,
            $this->security,
            $this->tabCountKpiCacheHelper,
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
            mesDossiersMessagesUsagers: $mesDossiers
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
        $countAfermer = new CountAfermer(1, 1, 1);
        $countAverifier = 4;

        $this->signalementRepository
            ->expects($this->never())
            ->method('countNouveauxDossiersKpi')
            ->with($territories)
            ->willReturn($countNouveaux);

        $this->signalementRepository
            ->expects($this->never())
            ->method('countAllDossiersAferme')
            ->with($user, $params)
            ->willReturn($countAfermer);

        $this->suiviRepository
            ->expects($this->never())
            ->method('countAllMessagesUsagers')
            ->with($user, $params)
            ->willReturn($countMessages);

        $this->signalementRepository
            ->expects($this->never())
            ->method('countSignalementsSansSuiviPartenaireDepuis60Jours')
            ->with($user, $params)
            ->willReturn($countAverifier);

        $this->tabCountKpiCacheHelper
            ->method('getOrSet')
            ->willReturnCallback(function ($kpiName) use ($countNouveaux, $countAfermer, $countMessages, $countAverifier) {
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
            ->setMesDossiers($mesDossiers, $mesDossiers)
            ->setSearchAverifier(null, [])
            ->withTabCountKpi()
            ->build();

        $this->assertInstanceOf(TabCountKpi::class, $tabCountKpi);
        $this->assertSame(10, $tabCountKpi->countNouveauxDossiers);
        $this->assertSame(6, $tabCountKpi->countDossiersMessagesUsagers);
        $this->assertSame(3, $tabCountKpi->countDossiersAFermer);
        $this->assertSame($countAverifier, $tabCountKpi->countDossiersAVerifier);
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
            mesDossiersMessagesUsagers: $mesDossiers
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
        $countAfermer = new CountAfermer(1, 0, 0);
        $countAverifier = 4;

        $this->signalementRepository
            ->expects($this->never())
            ->method('countNouveauxDossiersKpi')
            ->with($territories, $user)
            ->willReturn($countNouveaux);

        $this->suiviRepository
            ->expects($this->never())
            ->method('countAllMessagesUsagers')
            ->with($user, $params)
            ->willReturn($countMessages);

        $this->signalementRepository
            ->expects($this->never())
            ->method('countAllDossiersAferme')
            ->with($user, $params)
            ->willReturn($countAfermer);

        $this->signalementRepository
            ->expects($this->never())
            ->method('countSignalementsSansSuiviPartenaireDepuis60Jours')
            ->with($user, $params)
            ->willReturn($countAverifier);

        $this->tabCountKpiCacheHelper
            ->method('getOrSet')
            ->willReturnCallback(function ($kpiName) use ($countNouveaux, $countAfermer, $countMessages, $countAverifier) {
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
            ->setMesDossiers($mesDossiers, $mesDossiers)
            ->setSearchAverifier(null, [])
            ->withTabCountKpi()
            ->build();

        $this->assertSame(6, $tabCountKpi->countNouveauxDossiers);
        $this->assertSame(5, $tabCountKpi->countDossiersMessagesUsagers);
        $this->assertSame(1, $tabCountKpi->countDossiersAFermer);
        $this->assertSame($countAverifier, $tabCountKpi->countDossiersAVerifier);
    }
}
