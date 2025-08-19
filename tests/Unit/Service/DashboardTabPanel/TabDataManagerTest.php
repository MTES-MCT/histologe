<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\DashboardTabPanel;

use App\Dto\CountPartner;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\User;
use App\Repository\JobEventRepository;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\DashboardTabPanel\Kpi\TabCountKpiBuilder;
use App\Service\DashboardTabPanel\TabDataManager;
use App\Service\DashboardTabPanel\TabDossier;
use App\Service\DashboardTabPanel\TabDossierResult;
use App\Service\DashboardTabPanel\TabQueryParameters;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class TabDataManagerTest extends TestCase
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
            $this->tabCountKpiBuilder
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
        $this->assertSame('10/06/2024', $result[0]->derniereActionAt);
        $this->assertSame('OUI', $result[0]->actionDepuis);
        $this->assertSame('/bo/signalements/uuid-123', $result[0]->lien);
    }

    public function testCountUsersPendingToArchiveReturnsCount(): void
    {
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
            $this->tabCountKpiBuilder
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
            $this->tabCountKpiBuilder
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
            $this->tabCountKpiBuilder
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
            $this->tabCountKpiBuilder
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
            $this->tabCountKpiBuilder
        );

        $result = $tabDataManager->getDossiersDemandesFermetureByUsager();

        $this->assertInstanceOf(TabDossierResult::class, $result);
        $this->assertSame($expectedDossiers, $result->dossiers);
        $this->assertSame($expectedCount, $result->count);
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
            $this->tabCountKpiBuilder
        );

        $result = $tabDataManager->getDossiersRelanceSansReponse($params);

        $this->assertInstanceOf(TabDossierResult::class, $result);
        $this->assertSame($expectedDossiers, $result->dossiers);
        $this->assertSame($expectedCount, $result->count);
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
            $this->tabCountKpiBuilder
        );

        $result = $tabDataManager->getDossiersFermePartenaireTous($params);

        $this->assertInstanceOf(TabDossierResult::class, $result);
        $this->assertSame($expectedDossiers, $result->dossiers);
        $this->assertSame($expectedCount, $result->count);
    }
}
