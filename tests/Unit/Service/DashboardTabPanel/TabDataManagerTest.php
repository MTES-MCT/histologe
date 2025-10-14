<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\DashboardTabPanel;

use App\Dto\CountPartner;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\User;
use App\Repository\JobEventRepository;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\DashboardTabPanel\Kpi\TabCountKpi;
use App\Service\DashboardTabPanel\Kpi\TabCountKpiBuilder;
use App\Service\DashboardTabPanel\TabDataManager;
use App\Service\DashboardTabPanel\TabDossier;
use App\Service\DashboardTabPanel\TabDossierResult;
use App\Service\DashboardTabPanel\TabQueryParameters;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;

class TabDataManagerTest extends WebTestCase
{
    protected MockObject|Security $security;
    protected MockObject|JobEventRepository $jobEventRepository;
    protected MockObject|SuiviRepository $suiviRepository;
    protected MockObject|TerritoryRepository $territoryRepository;
    protected MockObject|UserRepository $userRepository;
    protected MockObject|PartnerRepository $partnerRepository;
    protected MockObject|SignalementRepository $signalementRepository;
    protected MockObject|TabCountKpiBuilder $tabCountKpiBuilder;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->jobEventRepository = $this->createMock(JobEventRepository::class);
        $this->suiviRepository = $this->createMock(SuiviRepository::class);
        $this->territoryRepository = $this->createMock(TerritoryRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->partnerRepository = $this->createMock(PartnerRepository::class);
        $this->signalementRepository = $this->createMock(SignalementRepository::class);
        $this->tabCountKpiBuilder = $this->createMock(TabCountKpiBuilder::class);
    }

    public function testGetDernierActionDossiersReturnsExpectedTabDossier(): void
    {
        /** @var MockObject&User $user */
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);

        $signalementStatus = SignalementStatus::ACTIVE;
        $suiviCategory = SuiviCategory::MESSAGE_PARTNER;

        $this->suiviRepository->method('findLastSignalementsWithUserSuivi')->willReturn([
            [
                'nomOccupant' => 'Dupont',
                'prenomOccupant' => 'Jean',
                'reference' => '2023-001',
                'adresseOccupant' => '1 rue de Paris',
                'statut' => $signalementStatus,
                'suiviCategory' => $suiviCategory,
                'suiviIsPublic' => true,
                'suiviCreatedAt' => new \DateTimeImmutable('2024-06-10'),
                'hasNewerSuivi' => true,
                'uuid' => 'uuid-123',
            ],
        ]);

        $tabDataManager = new TabDataManager(
            $this->security,
            $this->jobEventRepository,
            $this->suiviRepository,
            $this->territoryRepository,
            $this->userRepository,
            $this->partnerRepository,
            $this->signalementRepository,
            $this->tabCountKpiBuilder,
        );

        $result = $tabDataManager->getDernierActionDossiers();
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(TabDossier::class, $result[0]);
        $this->assertSame('Dupont', $result[0]->nomDeclarant);
        $this->assertSame('Jean', $result[0]->prenomDeclarant);
        $this->assertSame('#2023-001', $result[0]->reference);
        $this->assertSame('1 rue de Paris', $result[0]->adresse);
        $this->assertSame('en cours', $result[0]->statut);
        $this->assertSame('Suivi visible par l\'usager', $result[0]->derniereAction);
        $this->assertSame('10/06/2024', $result[0]->derniereActionAt->format('d/m/Y'));
        $this->assertSame('OUI', $result[0]->actionDepuis);
        $this->assertSame('uuid-123', $result[0]->uuid);
    }

    public function testCountUsersPendingToArchiveReturnsCount(): void
    {
        /** @var MockObject&User $user */
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);
        $this->userRepository->method('findUsersPendingToArchive')->willReturn([1, 2, 3]);

        $tabDataManager = new TabDataManager(
            $this->security,
            $this->jobEventRepository,
            $this->suiviRepository,
            $this->territoryRepository,
            $this->userRepository,
            $this->partnerRepository,
            $this->signalementRepository,
            $this->tabCountKpiBuilder,
        );

        $result = $tabDataManager->countUsersPendingToArchive();
        $this->assertSame(3, $result);
    }

    public function testCountPartenairesNonNotifiablesReturnsCount(): void
    {
        $countPartnerDto = $this->createMock(CountPartner::class);
        $countPartnerDto->method('getNonNotifiables')->willReturn(7);
        $this->partnerRepository->method('countPartnerNonNotifiables')->willReturn($countPartnerDto);

        $tabDataManager = new TabDataManager(
            $this->security,
            $this->jobEventRepository,
            $this->suiviRepository,
            $this->territoryRepository,
            $this->userRepository,
            $this->partnerRepository,
            $this->signalementRepository,
            $this->tabCountKpiBuilder,
        );

        $result = $tabDataManager->countPartenairesNonNotifiables();
        $this->assertSame(7, $result);
    }

    public function testCountPartenairesInterfacesReturnsCount(): void
    {
        $this->partnerRepository->method('countPartnerInterfaces')->willReturn(5);

        $tabDataManager = new TabDataManager(
            $this->security,
            $this->jobEventRepository,
            $this->suiviRepository,
            $this->territoryRepository,
            $this->userRepository,
            $this->partnerRepository,
            $this->signalementRepository,
            $this->tabCountKpiBuilder,
        );

        $result = $tabDataManager->countPartenairesInterfaces();
        $this->assertSame(5, $result);
    }

    public function testGetInterconnexionsReturnsExpectedArray(): void
    {
        $lastSynchro = [['createdAt' => new \DateTimeImmutable('2024-06-10 10:00:00')]];
        $lastError = [['createdAt' => new \DateTimeImmutable('2024-06-11 11:00:00')]];
        $this->jobEventRepository->method('findLastJobEventByTerritory')
            ->willReturnOnConsecutiveCalls($lastSynchro, $lastError);

        $tabDataManager = new TabDataManager(
            $this->security,
            $this->jobEventRepository,
            $this->suiviRepository,
            $this->territoryRepository,
            $this->userRepository,
            $this->partnerRepository,
            $this->signalementRepository,
            $this->tabCountKpiBuilder,
        );

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

        $this->signalementRepository
            ->method('findDossiersNoAgentFrom')
            ->with(AffectationStatus::ACCEPTED, $params)
            ->willReturn($expectedDossiers);
        $this->signalementRepository
            ->method('countDossiersNoAgentFrom')
            ->with(AffectationStatus::ACCEPTED, $params)
            ->willReturn($expectedCount);
        $tabDataManager = new TabDataManager(
            $this->security,
            $this->jobEventRepository,
            $this->suiviRepository,
            $this->territoryRepository,
            $this->userRepository,
            $this->partnerRepository,
            $this->signalementRepository,
            $this->tabCountKpiBuilder
        );
        $result = $tabDataManager->getDossiersNoAgentWithCount($params, AffectationStatus::ACCEPTED);

        $this->assertInstanceOf(TabDossierResult::class, $result);
        $this->assertSame($expectedDossiers, $result->dossiers);
        $this->assertSame($expectedCount, $result->count);
    }

    public function testGetDossiersDemandesFermetureByUsagerReturnsExpectedResult(): void
    {
        $expectedDossiers = [['id' => 1], ['id' => 2]];
        $expectedCount = 2;

        $this->signalementRepository
            ->method('findDossiersDemandesFermetureByUsager')
            ->with(null)
            ->willReturn($expectedDossiers);
        $this->signalementRepository
            ->method('countDossiersDemandesFermetureByUsager')
            ->with(null)
            ->willReturn($expectedCount);
        $tabDataManager = new TabDataManager(
            $this->security,
            $this->jobEventRepository,
            $this->suiviRepository,
            $this->territoryRepository,
            $this->userRepository,
            $this->partnerRepository,
            $this->signalementRepository,
            $this->tabCountKpiBuilder,
        );
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
        $this->suiviRepository->method('findSuivisUsagersWithoutAskFeedbackBefore')->with($user)->willReturn([
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
        $this->suiviRepository->method('countSuivisUsagersWithoutAskFeedbackBefore')->with($user)->willReturn(1);

        $tabDataManager = new TabDataManager(
            $this->security,
            $this->jobEventRepository,
            $this->suiviRepository,
            $this->territoryRepository,
            $this->userRepository,
            $this->partnerRepository,
            $this->signalementRepository,
            $this->tabCountKpiBuilder,
        );
        $result = $tabDataManager->getMessagesUsagersNouveauxMessages();
        $this->assertCount(1, $result->dossiers);
        $this->assertSame(1, $result->count);
        $this->assertSame('Martin', $result->dossiers[0]->nomDeclarant);
        $this->assertSame('Alice', $result->dossiers[0]->prenomDeclarant);
        $this->assertSame('#2024-001', $result->dossiers[0]->reference);
        $this->assertSame('uuid-456', $result->dossiers[0]->uuid);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->dossiers[0]->messageAt);
    }

    public function testGetDossiersRelanceSansReponseReturnsExpectedResult(): void
    {
        $expectedDossiers = [['id' => 10]];
        $expectedCount = 1;
        $params = new TabQueryParameters(null, null);

        $this->signalementRepository
            ->method('findSignalementsAvecRelancesSansReponse')
            ->with($params)
            ->willReturn($expectedDossiers);
        $this->signalementRepository
            ->method('countSignalementsAvecRelancesSansReponse')
            ->with($params)
            ->willReturn($expectedCount);
        $tabDataManager = new TabDataManager(
            $this->security,
            $this->jobEventRepository,
            $this->suiviRepository,
            $this->territoryRepository,
            $this->userRepository,
            $this->partnerRepository,
            $this->signalementRepository,
            $this->tabCountKpiBuilder,
        );
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
        $this->suiviRepository->method('findSuivisPostCloture')->with($user)->willReturn([
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
        $this->suiviRepository->method('countSuivisPostCloture')->with($user)->willReturn(1);

        $tabDataManager = new TabDataManager(
            $this->security,
            $this->jobEventRepository,
            $this->suiviRepository,
            $this->territoryRepository,
            $this->userRepository,
            $this->partnerRepository,
            $this->signalementRepository,
            $this->tabCountKpiBuilder,
        );
        $result = $tabDataManager->getMessagesUsagersMessageApresFermeture();
        $this->assertCount(1, $result->dossiers);
        $this->assertSame(1, $result->count);
        $this->assertSame('#2024-002', $result->dossiers[0]->reference);
        $this->assertSame('Durand', $result->dossiers[0]->nomDeclarant);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->dossiers[0]->clotureAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->dossiers[0]->messageAt);
    }

    public function testGetDossiersFermePartenaireTousReturnsExpectedResult(): void
    {
        $expectedDossiers = [['id' => 99]];
        $expectedCount = 1;
        $params = new TabQueryParameters(null, null);

        $this->signalementRepository
            ->method('findDossiersFermePartenaireTous')
            ->with($params)
            ->willReturn($expectedDossiers);
        $this->signalementRepository
            ->method('countDossiersFermePartenaireTous')
            ->with($params)
            ->willReturn($expectedCount);
        $tabDataManager = new TabDataManager(
            $this->security,
            $this->jobEventRepository,
            $this->suiviRepository,
            $this->territoryRepository,
            $this->userRepository,
            $this->partnerRepository,
            $this->signalementRepository,
            $this->tabCountKpiBuilder,
        );
        $result = $tabDataManager->getDossiersFermePartenaireTous($params);

        $this->assertInstanceOf(TabDossierResult::class, $result);
        $this->assertSame($expectedDossiers, $result->dossiers);
        $this->assertSame($expectedCount, $result->count);
    }

    public function testGetMessagesUsagersMessagesSansReponseReturnsExpectedResult(): void
    {
        /** @var MockObject&User $user */
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);
        $this->suiviRepository->method('findSuivisUsagerOrPoursuiteWithAskFeedbackBefore')->with($user)->willReturn([
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
        $this->suiviRepository->method('countSuivisUsagerOrPoursuiteWithAskFeedbackBefore')->with($user)->willReturn(1);

        $tabDataManager = new TabDataManager(
            $this->security,
            $this->jobEventRepository,
            $this->suiviRepository,
            $this->territoryRepository,
            $this->userRepository,
            $this->partnerRepository,
            $this->signalementRepository,
            $this->tabCountKpiBuilder,
        );

        $result = $tabDataManager->getMessagesUsagersMessagesSansReponse();
        $this->assertCount(1, $result->dossiers);
        $this->assertSame(1, $result->count);
        $this->assertSame('Lemoine', $result->dossiers[0]->nomDeclarant);
        $this->assertSame('Claire', $result->dossiers[0]->prenomDeclarant);
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
        $this->signalementRepository->method('findSignalementsSansSuiviPartenaireDepuis60Jours')->with($user)->willReturn([
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
        $this->signalementRepository->method('countSignalementsSansSuiviPartenaireDepuis60Jours')->with($user)->willReturn(1);

        $tabDataManager = new TabDataManager(
            $this->security,
            $this->jobEventRepository,
            $this->suiviRepository,
            $this->territoryRepository,
            $this->userRepository,
            $this->partnerRepository,
            $this->signalementRepository,
            $this->tabCountKpiBuilder,
        );

        $result = $tabDataManager->getDossiersAVerifierSansActivitePartenaires();
        $this->assertCount(1, $result->dossiers);
        $this->assertSame(1, $result->count);
        $this->assertSame('Lemoine', $result->dossiers[0]->nomDeclarant);
        $this->assertSame('Claire', $result->dossiers[0]->prenomDeclarant);
        $this->assertSame('#2024-003', $result->dossiers[0]->reference);
        $this->assertSame(3, $result->dossiers[0]->derniereActionPartenaireDaysAgo);
        $this->assertSame(SuiviCategory::MESSAGE_PARTNER->label(), $result->dossiers[0]->derniereActionTypeSuivi);
        $this->assertSame('uuid-999', $result->dossiers[0]->uuid);
        $this->assertInstanceOf('DateTimeImmutable', $result->dossiers[0]->derniereActionAt);
    }

    public function testCountDataKpi(): void
    {
        $tabDataManager = new TabDataManager(
            $this->security,
            $this->jobEventRepository,
            $this->suiviRepository,
            $this->territoryRepository,
            $this->userRepository,
            $this->partnerRepository,
            $this->signalementRepository,
            $this->tabCountKpiBuilder,
        );

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
        $this->suiviRepository->method('findLastSignalementsWithOtherUserSuivi')
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
                    'suiviIsPublic' => true,
                    'derniereActionPartenaireNom' => 'SUPER PARTENAIRE',
                    'derniereActionPartenaireNomAgent' => 'Robert',
                    'derniereActionPartenairePrenomAgent' => 'Sophie',
                ],
            ]);

        $tabDataManager = new TabDataManager(
            $this->security,
            $this->jobEventRepository,
            $this->suiviRepository,
            $this->territoryRepository,
            $this->userRepository,
            $this->partnerRepository,
            $this->signalementRepository,
            $this->tabCountKpiBuilder,
        );

        $result = $tabDataManager->getDossiersActiviteRecente($params);
        $this->assertCount(1, $result->dossiers);
        $this->assertSame(1, $result->count);
        $this->assertSame(SignalementStatus::ACTIVE->label(), $result->dossiers[0]->statut);
        $this->assertSame('#2024-003', $result->dossiers[0]->reference);
        $this->assertSame('Lemoine', $result->dossiers[0]->nomDeclarant);
        $this->assertSame('Claire', $result->dossiers[0]->prenomDeclarant);
        $this->assertInstanceOf('DateTimeImmutable', $result->dossiers[0]->derniereActionAt);
        $this->assertSame('30 boulevard Nation 62100 Alès', $result->dossiers[0]->adresse);
        $this->assertSame('Suivi visible par l\'usager', $result->dossiers[0]->derniereAction);
        $this->assertSame('Sophie', $result->dossiers[0]->derniereActionPartenairePrenomAgent);
        $this->assertSame('Robert', $result->dossiers[0]->derniereActionPartenaireNomAgent);
        $this->assertSame('SUPER PARTENAIRE', $result->dossiers[0]->derniereActionPartenaireNom);
        $this->assertSame('uuid-999', $result->dossiers[0]->uuid);
    }
}
