<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\DashboardTabPanel\TabBodyLoader;

use App\Entity\Address;
use App\Entity\ClubEvent;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\InterventionRepository;
use App\Service\ClubEventService;
use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyLoader\DossiersDernierActionTabBodyLoader;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;
use App\Service\DashboardTabPanel\TabQueryParameters;
use App\Service\TimezoneProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;

class DossiersDernierActionTabBodyLoaderTest extends KernelTestCase
{
    private ClubEventService $clubEventService;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->clubEventService = static::getContainer()->get(ClubEventService::class);
    }

    public function testLoadFillsTabBodyWithExpectedData(): void
    {
        /** @var User&Stub $user */
        $user = $this->createStub(User::class);
        $user->method('isSuperAdmin')->willReturn(true);
        /** @var Security&Stub $security */
        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturn(true);
        $security->method('getUser')->willReturn($user);

        /** @var TabDataManager&MockObject $tabDataManager */
        $tabDataManager = $this->createMock(TabDataManager::class);
        $tabQueryParameters = new TabQueryParameters(territoireId: 42);

        $expectedData = ['data' => 'bar', 'total' => 1, 'page' => 1];
        $expectedKpi = [
            'comptes_en_attente' => 3,
            'comptes_pb_email' => 0,
            'partenaires_non_notifiables' => 2,
            'partenaires_interfaces' => 1,
            'injonctions' => 2,
            'injonctions_nouveaux_messages_usager' => 0,
            'injonctions_nouveaux_messages_bailleur' => 0,
        ];
        $expectedInterconnexion = ['hasErrorsLastDay' => false];

        $tabDataManager->expects($this->once())
            ->method('getDernierActionDossiers')
            ->with($tabQueryParameters)
            ->willReturn($expectedData);
        $tabDataManager->expects($this->once())
            ->method('countInjonctions')
            ->with($tabQueryParameters)
            ->willReturn($expectedKpi['injonctions']);
        $tabDataManager->expects($this->exactly(2))
            ->method('countInjonctionsNouveauxMessages')
            ->willReturnCallback(static fn ($_params, string $type) => match ($type) {
                'usager' => $expectedKpi['injonctions_nouveaux_messages_usager'],
                'bailleur' => $expectedKpi['injonctions_nouveaux_messages_bailleur'],
                default => '',
            });
        $tabDataManager->expects($this->once())
            ->method('countUsersPendingToArchive')
            ->with($tabQueryParameters)
            ->willReturn($expectedKpi['comptes_en_attente']);
        $tabDataManager->expects($this->once())
            ->method('countPartenairesNonNotifiables')
            ->with($tabQueryParameters)
            ->willReturn($expectedKpi['partenaires_non_notifiables']);
        $tabDataManager->expects($this->once())
            ->method('countPartenairesInterfaces')
            ->with($tabQueryParameters)
            ->willReturn($expectedKpi['partenaires_interfaces']);
        $tabDataManager->expects($this->once())
            ->method('getInterconnexions')
            ->with($tabQueryParameters)
            ->willReturn($expectedInterconnexion);

        $loader = new DossiersDernierActionTabBodyLoader(
            $security,
            $tabDataManager,
            $this->clubEventService,
            $this->createStub(InterventionRepository::class),
            false,
        );
        $tabBody = new TabBody(
            type: TabBodyType::TAB_DATA_TYPE_DERNIER_ACTION_DOSSIERS,
            tabQueryParameters: $tabQueryParameters
        );

        $loader->load($tabBody);

        $data = $tabBody->getData();
        $this->assertIsArray($data);
        $this->assertSame($expectedData['data'], $data['data']);
        $this->assertSame($expectedData['total'], $tabBody->getCount());
        $this->assertSame($expectedData['page'], $data['page']);
        $this->assertSame($expectedKpi, $data['data_kpi']);
        $this->assertSame($expectedInterconnexion, $data['data_interconnexion']);
        $this->assertSame(42, $data['territory_id']);
        $this->assertSame('back/dashboard/tabs/accueil/_body_derniere_action_dossiers.html.twig', $tabBody->getTemplate());
    }

    public function testLoadFillsTabBodyWithExpectedDataAgent(): void
    {
        /** @var User&Stub $user */
        $user = $this->createStub(User::class);
        $user->method('isSuperAdmin')->willReturn(false);
        $user->method('isTerritoryAdmin')->willReturn(false);
        /** @var Security&Stub $security */
        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturn(true);
        $security->method('getUser')->willReturn($user);

        /** @var TabDataManager&MockObject $tabDataManager */
        $tabDataManager = $this->createMock(TabDataManager::class);
        $tabQueryParameters = new TabQueryParameters(territoireId: 42);

        $expectedData = ['data' => 'bar', 'total' => 1, 'page' => 1];

        $tabDataManager->expects($this->once())
            ->method('getDernierActionDossiers')
            ->with($tabQueryParameters)
            ->willReturn($expectedData);

        $tabDataManager->expects($this->never())
            ->method('countInjonctionsNouveauxMessages');

        $loader = new DossiersDernierActionTabBodyLoader(
            $security,
            $tabDataManager,
            $this->clubEventService,
            $this->createStub(InterventionRepository::class),
            false,
        );
        $tabBody = new TabBody(
            type: TabBodyType::TAB_DATA_TYPE_DERNIER_ACTION_DOSSIERS,
            tabQueryParameters: $tabQueryParameters
        );

        $loader->load($tabBody);

        $data = $tabBody->getData();
        $this->assertIsArray($data);
        $this->assertSame($expectedData['data'], $data['data']);
        $this->assertSame($expectedData['total'], $tabBody->getCount());
        $this->assertSame($expectedData['page'], $data['page']);
        $this->assertArrayNotHasKey('data_kpi', $data);
        $this->assertArrayNotHasKey('data_interconnexion', $data);
        $this->assertSame(42, $data['territory_id']);
        $this->assertSame('back/dashboard/tabs/accueil/_body_derniere_action_dossiers.html.twig', $tabBody->getTemplate());
    }

    public function testLoadWithCalendarNextEventIsVisiteWhenBeforeClubEvent(): void
    {
        $intervention = $this->createIntervention('2026-12-01 10:00');
        $clubEvent = $this->createClubEvent('2026-12-02 14:00');

        $data = $this->loadWithCalendar($intervention, $clubEvent);

        $this->assertEquals(['name' => 'Visite #2026-12', 'dateEvent' => $intervention->getScheduledAt()], $data['nextEvent']);
    }

    public function testLoadWithCalendarNextEventIsClubEventWhenBeforeVisite(): void
    {
        $intervention = $this->createIntervention('2026-12-03 10:00');
        $clubEvent = $this->createClubEvent('2026-12-02 14:00');

        $data = $this->loadWithCalendar($intervention, $clubEvent);

        $this->assertSame(['name' => 'Club SA', 'dateEvent' => $clubEvent->getDateEvent()], $data['nextEvent']);
    }

    public function testLoadWithCalendarNextEventIsVisiteWithoutClubEvent(): void
    {
        $intervention = $this->createIntervention('2026-12-01 10:00');

        $data = $this->loadWithCalendar($intervention, null);

        $this->assertEquals(['name' => 'Visite #2026-12', 'dateEvent' => $intervention->getScheduledAt()], $data['nextEvent']);
    }

    public function testLoadWithCalendarNextEventIsClubEventWithoutVisite(): void
    {
        $clubEvent = $this->createClubEvent('2026-12-02 14:00');

        $data = $this->loadWithCalendar(null, $clubEvent);

        $this->assertSame(['name' => 'Club SA', 'dateEvent' => $clubEvent->getDateEvent()], $data['nextEvent']);
    }

    public function testLoadWithCalendarWithoutNextEvent(): void
    {
        $data = $this->loadWithCalendar(null, null);

        $this->assertArrayNotHasKey('nextEvent', $data);
        $this->assertArrayNotHasKey('nextClubEvent', $data);
    }

    /**
     * Chargement par un agent (sans KPI), avec la fonctionnalité calendrier activée
     *
     * @return array<string, mixed>
     */
    private function loadWithCalendar(?Intervention $nextIntervention, ?ClubEvent $nextClubEvent): array
    {
        /** @var User&Stub $user */
        $user = $this->createStub(User::class);
        $user->method('isSuperAdmin')->willReturn(false);
        $user->method('isTerritoryAdmin')->willReturn(false);
        /** @var Security&Stub $security */
        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturn(true);
        $security->method('getUser')->willReturn($user);

        /** @var TabDataManager&Stub $tabDataManager */
        $tabDataManager = $this->createStub(TabDataManager::class);
        $tabDataManager->method('getDernierActionDossiers')->willReturn(['data' => [], 'total' => 0, 'page' => 1]);

        /** @var ClubEventService&Stub $clubEventService */
        $clubEventService = $this->createStub(ClubEventService::class);
        $clubEventService->method('getNextClubEventForUser')->willReturn($nextClubEvent);

        // Le filtre territoire du tableau de bord est transmis à la recherche de la prochaine visite
        /** @var InterventionRepository&MockObject $interventionRepository */
        $interventionRepository = $this->createMock(InterventionRepository::class);
        $interventionRepository->expects($this->once())
            ->method('findNextForUser')
            ->with($user, 42)
            ->willReturn($nextIntervention);

        $loader = new DossiersDernierActionTabBodyLoader(
            $security,
            $tabDataManager,
            $clubEventService,
            $interventionRepository,
            true,
        );
        $tabBody = new TabBody(
            type: TabBodyType::TAB_DATA_TYPE_DERNIER_ACTION_DOSSIERS,
            tabQueryParameters: new TabQueryParameters(territoireId: 42)
        );

        $loader->load($tabBody);

        $data = $tabBody->getData();
        $this->assertIsArray($data);

        return $data;
    }

    private function createIntervention(string $scheduledAt): Intervention
    {
        // getScheduledAt() convertit la date dans le fuseau horaire du territoire du signalement
        $territory = (new Territory())->setTimezone(TimezoneProvider::TIMEZONE_EUROPE_PARIS);
        $signalement = (new Signalement())
            ->setReference('2026-12')
            ->setAddress((new Address())->setTerritory($territory));

        return (new Intervention())
            ->setScheduledAt(new \DateTimeImmutable($scheduledAt))
            ->setSignalement($signalement);
    }

    private function createClubEvent(string $dateEvent): ClubEvent
    {
        return (new ClubEvent())
            ->setName('Club SA')
            ->setDateEvent(new \DateTimeImmutable($dateEvent));
    }
}
