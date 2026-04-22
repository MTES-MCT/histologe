<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\DashboardTabPanel;

use App\Dto\CountPartner;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\User;
use App\Repository\JobEventRepository;
use App\Repository\Query\Dashboard\DossiersActiviteRecenteQuery;
use App\Repository\Query\Dashboard\DossiersAvecRelanceSansReponseQuery;
use App\Repository\Query\Dashboard\DossiersQuery;
use App\Repository\Query\Dashboard\DossiersSansSuivisPartenaireQuery;
use App\Repository\Query\Dashboard\DossiersSuivisUsagerQuery;
use App\Repository\Query\Dashboard\DossiersUndeliverableEmailQuery;
use App\Repository\Query\Dashboard\KpiQuery;
use App\Repository\Query\Dashboard\SignalementsSansAffectationAccepteeQuery;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\DashboardTabPanel\Kpi\TabCountKpi;
use App\Service\DashboardTabPanel\Kpi\TabCountKpiBuilder;
use App\Service\DashboardTabPanel\TabDataManager;
use App\Service\DashboardTabPanel\TabDossier;
use App\Service\DashboardTabPanel\TabDossierResult;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;

class TabDataManagerTest extends WebTestCase
{
    protected MockObject&Security $security;
    protected MockObject&JobEventRepository $jobEventRepository;
    protected MockObject&TerritoryRepository $territoryRepository;
    protected MockObject&UserRepository $userRepository;
    protected MockObject&SignalementRepository $signalementRepository;
    protected MockObject&TabCountKpiBuilder $tabCountKpiBuilder;
    protected MockObject&SignalementsSansAffectationAccepteeQuery $signalementsSansAffectationAccepteeQuery;
    protected MockObject&DossiersQuery $dossiersQuery;
    protected MockObject&DossiersActiviteRecenteQuery $dossiersActiviteRecenteQuery;
    protected MockObject&DossiersAvecRelanceSansReponseQuery $dossiersAvecRelanceSansReponseQuery;
    protected MockObject&DossiersSuivisUsagerQuery $dossiersSuivisUsagerQuery;
    protected MockObject&DossiersSansSuivisPartenaireQuery $dossiersSansSuivisPartenaireQuery;
    protected MockObject&DossiersUndeliverableEmailQuery $dossiersUndeliverableEmailQuery;
    protected MockObject&KpiQuery $kpiQuery;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->jobEventRepository = $this->createMock(JobEventRepository::class);
        $this->territoryRepository = $this->createMock(TerritoryRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->signalementRepository = $this->createMock(SignalementRepository::class);
        $this->tabCountKpiBuilder = $this->createMock(TabCountKpiBuilder::class);
        $this->signalementsSansAffectationAccepteeQuery = $this->createMock(SignalementsSansAffectationAccepteeQuery::class);
        $this->dossiersQuery = $this->createMock(DossiersQuery::class);
        $this->dossiersActiviteRecenteQuery = $this->createMock(DossiersActiviteRecenteQuery::class);
        $this->dossiersAvecRelanceSansReponseQuery = $this->createMock(DossiersAvecRelanceSansReponseQuery::class);
        $this->dossiersSuivisUsagerQuery = $this->createMock(DossiersSuivisUsagerQuery::class);
        $this->dossiersSansSuivisPartenaireQuery = $this->createMock(DossiersSansSuivisPartenaireQuery::class);
        $this->dossiersUndeliverableEmailQuery = $this->createMock(DossiersUndeliverableEmailQuery::class);
        $this->kpiQuery = $this->createMock(KpiQuery::class);
    }

    private function getTabDataManager(): TabDataManager
    {
        return new TabDataManager(
            $this->security,
            $this->jobEventRepository,
            $this->territoryRepository,
            $this->userRepository,
            $this->tabCountKpiBuilder,
            $this->signalementsSansAffectationAccepteeQuery,
            $this->dossiersQuery,
            $this->dossiersActiviteRecenteQuery,
            $this->dossiersAvecRelanceSansReponseQuery,
            $this->dossiersSuivisUsagerQuery,
            $this->dossiersSansSuivisPartenaireQuery,
            $this->dossiersUndeliverableEmailQuery,
            $this->kpiQuery
        );
    }

    public function testGetDernierActionDossiersReturnsExpectedTabDossier(): void
    {
        /** @var MockObject&User $user */
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);

        $signalementStatus = SignalementStatus::ACTIVE;
        $suiviCategory = SuiviCategory::MESSAGE_PARTNER;

        $rawData = [
            [
                'nomOccupant' => 'Dupont',
                'prenomOccupant' => 'Jean',
                'reference' => '2023-001',
                'adresseOccupant' => '1 rue de Paris',
                'statut' => $signalementStatus,
                'suiviCategory' => $suiviCategory,
                'suiviIsPublic' => true,// TODO : à changer
                'suiviCreatedAt' => new \DateTimeImmutable('2024-06-10'),
                'hasNewerSuivi' => true,
                'uuid' => 'uuid-123',
            ],
        ];

        /** @var MockObject&Paginator $paginator */
        $paginator = $this->createMock(Paginator::class);
        $paginator->method('getIterator')->willReturn(new \ArrayIterator($rawData));
        $paginator->method('count')->willReturn(1);

        $this->dossiersActiviteRecenteQuery
            ->method('findPaginatedLastSignalementsWithUserSuivi')
            ->willReturn($paginator);

        $this->dossiersActiviteRecenteQuery
            ->method('countLastSignalementsWithUserSuivi')
            ->willReturn(1);

        $tabDataManager = $this->getTabDataManager();

        $result = $tabDataManager->getDernierActionDossiers();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertSame(1, $result['total']);
        $this->assertSame(1, $result['page']);
        $firstResult = $result['data'][0];
        $this->assertInstanceOf(TabDossier::class, $firstResult);
        $this->assertSame('Dupont', $firstResult->nomOccupant);
        $this->assertSame('Jean', $firstResult->prenomOccupant);
        $this->assertSame('#2023-001', $firstResult->reference);
        $this->assertSame('1 rue de Paris', $firstResult->adresse);
        $this->assertSame('en cours', $firstResult->statut);
        $this->assertSame('Suivi visible par l\'usager', $firstResult->derniereAction);
        $this->assertSame('10/06/2024', $firstResult->derniereActionAt->format('d/m/Y'));
        $this->assertSame('OUI', $firstResult->actionDepuis);
        $this->assertSame('uuid-123', $firstResult->uuid);
        $this->assertSame('OUI', $firstResult->actionDepuis);
        $this->assertSame('uuid-123', $firstResult->uuid);
    }

    public function testCountInjonctions(): void
    {
        /** @var MockObject&User $user */
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);
        $this->kpiQuery->method('countInjonctions')->willReturn(5);

        $tabDataManager = $this->getTabDataManager();

        $result = $tabDataManager->countInjonctions();
        $this->assertSame(5, $result);
    }

    public function testCountUsersPendingToArchiveReturnsCount(): void
    {
        /** @var MockObject&User $user */
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);
        $this->userRepository->method('findUsersPendingToArchive')->willReturn([1, 2, 3]);

        $tabDataManager = $this->getTabDataManager();

        $result = $tabDataManager->countUsersPendingToArchive();
        $this->assertSame(3, $result);
    }

    public function testCountPartenairesNonNotifiablesReturnsCount(): void
    {
        $countPartnerDto = $this->createMock(CountPartner::class);
        $countPartnerDto->method('getNonNotifiables')->willReturn(7);
        $this->kpiQuery->method('countPartnerNonNotifiables')->willReturn($countPartnerDto);

        $tabDataManager = $this->getTabDataManager();

        $result = $tabDataManager->countPartenairesNonNotifiables();
        $this->assertSame(7, $result);
    }

    public function testCountPartenairesInterfacesReturnsCount(): void
    {
        $this->kpiQuery->method('countPartnerInterfaces')->willReturn(5);

        $tabDataManager = $this->getTabDataManager();

        $result = $tabDataManager->countPartenairesInterfaces();
        $this->assertSame(5, $result);
    }

    public function testGetInterconnexionsReturnsExpectedArray(): void
    {
        $lastSynchro = [['createdAt' => new \DateTimeImmutable('2024-06-10 10:00:00')]];
        $lastError = [['createdAt' => new \DateTimeImmutable('2024-06-11 11:00:00')]];
        $this->jobEventRepository->method('findLastJobEventByTerritory')
            ->willReturnOnConsecutiveCalls($lastSynchro, $lastError);

        $tabDataManager = $this->getTabDataManager();

        $result = $tabDataManager->getInterconnexions();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('hasErrorsLastDay', $result);
        $this->assertArrayHasKey('firstErrorLastDayAt', $result);
        $this->assertArrayHasKey('LastSyncAt', $result);
        $this->assertTrue($result['hasErrorsLastDay']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result['firstErrorLastDayAt']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result['LastSyncAt']);
        $this->assertEquals('2024-06-11 11:00:00', $result['firstErrorLastDayAt']->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-06-10 10:00:00', $result['LastSyncAt']->format('Y-m-d H:i:s'));
    }

    public function testGetDossiersNoAgentWithCountReturnsExpectedResult(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        /** @var User $user */
        $user = $userRepository->findOneBy(['email' => 'user-13-01@signal-logement.fr']);
        $this->security->method('getUser')->willReturn($user);

        $expectedDossiers = [];
        $expectedCount = 0;
        $params = new TabQueryParameters(null, null);

        $this->dossiersQuery
            ->method('findDossiersNoAgentFrom')
            ->with(AffectationStatus::ACCEPTED, $params)
            ->willReturn($expectedDossiers);
        $this->dossiersQuery
            ->method('countDossiersNoAgentFrom')
            ->with(AffectationStatus::ACCEPTED, $params)
            ->willReturn($expectedCount);
        $tabDataManager = $this->getTabDataManager();
        $result = $tabDataManager->getDossiersNoAgentWithCount($params, AffectationStatus::ACCEPTED);

        $this->assertInstanceOf(TabDossierResult::class, $result);
        $this->assertSame($expectedDossiers, $result->dossiers);
        $this->assertSame($expectedCount, $result->count);
    }

    public function testGetDossiersDemandesFermetureByUsagerReturnsExpectedResult(): void
    {
        $expectedDossiers = [['id' => 1], ['id' => 2]];
        $expectedCount = 2;

        $this->dossiersQuery
            ->method('findDossiersDemandesFermetureByUsager')
            ->with(null)
            ->willReturn($expectedDossiers);
        $this->dossiersQuery
            ->method('countDossiersDemandesFermetureByUsager')
            ->with(null)
            ->willReturn($expectedCount);
        $tabDataManager = $this->getTabDataManager();
        $result = $tabDataManager->getDossiersDemandesFermetureByUsager();

        $this->assertInstanceOf(TabDossierResult::class, $result);
        $this->assertSame($expectedDossiers, $result->dossiers);
        $this->assertSame($expectedCount, $result->count);
    }

    public function testGetMessagesUsagersNouveauxMessagesReturnsExpectedResult(): void
    {
        /** @var MockObject&User $user */
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);
        $this->dossiersSuivisUsagerQuery->method('findSuivisUsagersWithoutAskFeedbackBefore')->with($user)->willReturn([
            [
                'nomOccupant' => 'Martin',
                'prenomOccupant' => 'Alice',
                'reference' => '2024-001',
                'adresse' => '10 rue Victor Hugo',
                'messageAt' => '2024-06-15 14:00:00',
                'messageSuiviByNom' => 'Dupont',
                'messageSuiviByPrenom' => 'Jean',
                'messageByProfileDeclarant' => true,
                'uuid' => 'uuid-456',
            ],
        ]);
        $this->dossiersSuivisUsagerQuery->method('countSuivisUsagersWithoutAskFeedbackBefore')->with($user)->willReturn(1);

        $tabDataManager = $this->getTabDataManager();
        $result = $tabDataManager->getMessagesUsagersNouveauxMessages();
        $this->assertCount(1, $result->dossiers);
        $this->assertSame(1, $result->count);
        $this->assertSame('Martin', $result->dossiers[0]->nomOccupant);
        $this->assertSame('Alice', $result->dossiers[0]->prenomOccupant);
        $this->assertSame('#2024-001', $result->dossiers[0]->reference);
        $this->assertSame('uuid-456', $result->dossiers[0]->uuid);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->dossiers[0]->messageAt);
    }

    public function testGetDossiersRelanceSansReponseReturnsExpectedResult(): void
    {
        $expectedDossiers = [['id' => 10]];
        $expectedCount = 1;
        $params = new TabQueryParameters(null, null);

        $this->dossiersAvecRelanceSansReponseQuery
            ->method('findSignalements')
            ->with($params)
            ->willReturn($expectedDossiers);
        $this->dossiersAvecRelanceSansReponseQuery
            ->method('countSignalements')
            ->with($params)
            ->willReturn($expectedCount);
        $tabDataManager = $this->getTabDataManager();
        $result = $tabDataManager->getDossiersRelanceSansReponse($params);

        $this->assertInstanceOf(TabDossierResult::class, $result);
        $this->assertSame($expectedDossiers, $result->dossiers);
        $this->assertSame($expectedCount, $result->count);
    }

    public function testGetMessagesUsagersMessageApresFermetureReturnsExpectedResult(): void
    {
        /** @var MockObject&User $user */
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);
        $this->dossiersSuivisUsagerQuery->method('findSuivisPostCloture')->with($user)->willReturn([
            [
                'nomOccupant' => 'Durand',
                'prenomOccupant' => 'Paul',
                'reference' => '2024-002',
                'adresse' => '20 avenue République',
                'clotureAt' => new \DateTimeImmutable('2024-05-01 09:00:00'),
                'messageAt' => '2024-06-01 10:00:00',
                'messageSuiviByNom' => 'Martin',
                'messageSuiviByPrenom' => 'Lucie',
                'messageByProfileDeclarant' => false,
                'uuid' => 'uuid-789',
            ],
        ]);
        $this->dossiersSuivisUsagerQuery->method('countSuivisPostCloture')->with($user)->willReturn(1);

        $tabDataManager = $this->getTabDataManager();
        $result = $tabDataManager->getMessagesUsagersMessageApresFermeture();
        $this->assertCount(1, $result->dossiers);
        $this->assertSame(1, $result->count);
        $this->assertSame('#2024-002', $result->dossiers[0]->reference);
        $this->assertSame('Durand', $result->dossiers[0]->nomOccupant);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->dossiers[0]->clotureAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->dossiers[0]->messageAt);
    }

    public function testGetDossiersFermePartenaireTousReturnsExpectedResult(): void
    {
        $expectedDossiers = [['id' => 99]];
        $expectedCount = 1;
        $params = new TabQueryParameters(null, null);

        $this->dossiersQuery
            ->method('findDossiersFermePartenaireTous')
            ->with($params)
            ->willReturn($expectedDossiers);
        $this->dossiersQuery
            ->method('countDossiersFermePartenaireTous')
            ->with($params)
            ->willReturn($expectedCount);
        $tabDataManager = $this->getTabDataManager();
        $result = $tabDataManager->getDossiersFermePartenaireTous($params);

        $this->assertInstanceOf(TabDossierResult::class, $result);
        $this->assertSame($expectedDossiers, $result->dossiers);
        $this->assertSame($expectedCount, $result->count);
    }

    public function testGetDossiersFermePartenaireCommuneReturnsExpectedResult(): void
    {
        $expectedDossiers = [['id' => 99]];
        $expectedCount = 1;
        $params = new TabQueryParameters(null, null);

        $this->dossiersQuery
            ->method('findDossiersFermePartenaireCommune')
            ->with($params)
            ->willReturn($expectedDossiers);
        $this->dossiersQuery
            ->method('countDossiersFermePartenaireCommune')
            ->with($params)
            ->willReturn($expectedCount);
        $tabDataManager = $this->getTabDataManager();
        $result = $tabDataManager->getDossiersFermePartenaireCommune($params);

        $this->assertInstanceOf(TabDossierResult::class, $result);
        $this->assertSame($expectedDossiers, $result->dossiers);
        $this->assertSame($expectedCount, $result->count);
    }

    public function testGetMessagesUsagersMessagesSansReponseReturnsExpectedResult(): void
    {
        /** @var MockObject&User $user */
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);
        $this->dossiersSuivisUsagerQuery->method('findSuivisUsagerOrPoursuiteWithAskFeedbackBefore')->with($user)->willReturn([
            [
                'nomOccupant' => 'Lemoine',
                'prenomOccupant' => 'Claire',
                'reference' => '2024-003',
                'adresse' => '30 boulevard Nation',
                'messageAt' => '2024-06-20 12:00:00',
                'messageSuiviByNom' => 'Robert',
                'messageSuiviByPrenom' => 'Sophie',
                'messageByProfileDeclarant' => true,
                'messageDaysAgo' => 3,
                'uuid' => 'uuid-999',
            ],
        ]);
        $this->dossiersSuivisUsagerQuery->method('countSuivisUsagerOrPoursuiteWithAskFeedbackBefore')->with($user)->willReturn(1);

        $tabDataManager = $this->getTabDataManager();

        $result = $tabDataManager->getMessagesUsagersMessagesSansReponse();
        $this->assertCount(1, $result->dossiers);
        $this->assertSame(1, $result->count);
        $this->assertSame('Lemoine', $result->dossiers[0]->nomOccupant);
        $this->assertSame('Claire', $result->dossiers[0]->prenomOccupant);
        $this->assertSame('#2024-003', $result->dossiers[0]->reference);
        $this->assertSame(3, $result->dossiers[0]->messageDaysAgo);
        $this->assertSame('uuid-999', $result->dossiers[0]->uuid);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->dossiers[0]->messageAt);
    }

    public function testGetDossiersAVerifierSansActivitePartenaires(): void
    {
        /** @var MockObject&User $user */
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);
        $this->dossiersSansSuivisPartenaireQuery->method('findSignalements')->with($user)->willReturn([
            [
                'nomOccupant' => 'Lemoine',
                'prenomOccupant' => 'Claire',
                'reference' => '2024-003',
                'adresse' => '30 boulevard Nation',
                'dernierSuiviAt' => '2024-06-20 12:00:00',
                'derniereActionPartenaireNom' => 'SUPER PARTENAIRE',
                'derniereActionPartenaireNomAgent' => 'Robert',
                'derniereActionPartenairePrenomAgent' => 'Sophie',
                'messageByProfileDeclarant' => true,
                'nbJoursDepuisDernierSuivi' => 3,
                'suiviCategory' => 'MESSAGE_PARTNER',
                'uuid' => 'uuid-999',
            ],
        ]);
        $this->dossiersSansSuivisPartenaireQuery->method('countSignalements')->with($user)->willReturn(1);

        $tabDataManager = $this->getTabDataManager();

        $result = $tabDataManager->getDossiersAVerifierSansActivitePartenaires();
        $this->assertCount(1, $result->dossiers);
        $this->assertSame(1, $result->count);
        $this->assertSame('Lemoine', $result->dossiers[0]->nomOccupant);
        $this->assertSame('Claire', $result->dossiers[0]->prenomOccupant);
        $this->assertSame('#2024-003', $result->dossiers[0]->reference);
        $this->assertSame(3, $result->dossiers[0]->derniereActionPartenaireDaysAgo);
        $this->assertSame(SuiviCategory::MESSAGE_PARTNER->label(), $result->dossiers[0]->derniereActionTypeSuivi);
        $this->assertSame('uuid-999', $result->dossiers[0]->uuid);
        $this->assertInstanceOf('DateTimeImmutable', $result->dossiers[0]->derniereActionAt);
    }

    public function testGetDossiersAVerifierSansAffectationAcceptee(): void
    {
        /** @var MockObject&User $user */
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);
        $this->signalementsSansAffectationAccepteeQuery->method('findSignalements')->with($user)->willReturn([
            [
                'nomOccupant' => 'Lemoine',
                'prenomOccupant' => 'Claire',
                'reference' => '2024-003',
                'adresse' => '30 boulevard Nation',
                'parc' => 'PRIVE',
                'lastAffectationAt' => '2024-06-20 12:00:00',
                'nbAffectations' => 3,
                'uuid' => 'uuid-999',
            ],
        ]);
        $this->signalementsSansAffectationAccepteeQuery->method('countSignalements')->with($user)->willReturn(1);

        $tabDataManager = $this->getTabDataManager();

        $result = $tabDataManager->getDossiersAVerifierSansAffectationAcceptee();
        $this->assertCount(1, $result->dossiers);
        $this->assertSame(1, $result->count);
        $this->assertSame('Lemoine', $result->dossiers[0]->nomOccupant);
        $this->assertSame('Claire', $result->dossiers[0]->prenomOccupant);
        $this->assertSame('#2024-003', $result->dossiers[0]->reference);
        $this->assertSame(3, $result->dossiers[0]->nbAffectations);
        $this->assertSame('uuid-999', $result->dossiers[0]->uuid);
        $this->assertInstanceOf('DateTimeImmutable', $result->dossiers[0]->derniereAffectationAt);
    }

    public function testCountDataKpi(): void
    {
        $tabDataManager = $this->getTabDataManager();

        $result = $tabDataManager->countDataKpi(
            territories: [],
            territoryId: 1,
            mesDossiersMessagesUsagers: 'oui',
            mesDossiersAverifier: null,
            mesDossiersActiviteRecente: null,
            queryCommune: null,
            partners: null
        );

        $this->assertInstanceOf(TabCountKpi::class, $result);
    }

    public function testGetDossiersActiviteRecente(): void
    {
        $user = $this->createMock(User::class);

        $this->security->method('getUser')->willReturn($user);
        $params = new TabQueryParameters(null, null);
        $this->dossiersActiviteRecenteQuery->method('findLastSignalementsWithOtherUserSuivi')
            ->with($user, $params, 11)
            ->willReturn([
                [
                    'reference' => '2024-003',
                    'nomOccupant' => 'Lemoine',
                    'prenomOccupant' => 'Claire',
                    'adresseOccupant' => '30 boulevard Nation 62100 Alès',
                    'uuid' => 'uuid-999',
                    'statut' => SignalementStatus::ACTIVE,
                    'suiviCreatedAt' => new \DateTimeImmutable('2024-06-20 12:00:00'),
                    'suiviCategory' => SuiviCategory::MESSAGE_PARTNER,
                    'suiviIsPublic' => true,// TODO : à changer
                    'derniereActionPartenaireNom' => 'SUPER PARTENAIRE',
                    'derniereActionPartenaireNomAgent' => 'Robert',
                    'derniereActionPartenairePrenomAgent' => 'Sophie',
                ],
            ]);

        $tabDataManager = $this->getTabDataManager();

        $result = $tabDataManager->getDossiersActiviteRecente($params);
        $this->assertCount(1, $result->dossiers);
        $this->assertSame(1, $result->count);
        $this->assertSame(SignalementStatus::ACTIVE->label(), $result->dossiers[0]->statut);
        $this->assertSame('#2024-003', $result->dossiers[0]->reference);
        $this->assertSame('Lemoine', $result->dossiers[0]->nomOccupant);
        $this->assertSame('Claire', $result->dossiers[0]->prenomOccupant);
        $this->assertInstanceOf('DateTimeImmutable', $result->dossiers[0]->derniereActionAt);
        $this->assertSame('30 boulevard Nation 62100 Alès', $result->dossiers[0]->adresse);
        $this->assertSame('Suivi visible par l\'usager', $result->dossiers[0]->derniereAction);
        $this->assertSame('Sophie', $result->dossiers[0]->derniereActionPartenairePrenomAgent);
        $this->assertSame('Robert', $result->dossiers[0]->derniereActionPartenaireNomAgent);
        $this->assertSame('SUPER PARTENAIRE', $result->dossiers[0]->derniereActionPartenaireNom);
        $this->assertSame('uuid-999', $result->dossiers[0]->uuid);
    }
}
