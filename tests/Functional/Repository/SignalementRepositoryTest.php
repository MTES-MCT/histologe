<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Enum\Qualification;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\DashboardTabPanel\TabDossier;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class SignalementRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private const USER_ADMIN = 'admin-01@signal-logement.fr';
    private const USER_PARTNER_TERRITORY_13 = 'user-13-01@signal-logement.fr';
    private const USER_ADMIN_MULTI_13 = 'admin-partenaire-multi-ter-13-01@signal-logement.fr';
    private const USER_AGENT_MULTI_34 = 'user-partenaire-multi-ter-34-30@signal-logement.fr';

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function testFindByReferenceChunkThrowException(): void
    {
        $this->expectException(NonUniqueResultException::class);

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => '01']);

        $signalementRepository->findByReferenceChunk(
            $territory,
            '2022-1'
        );
    }

    /**
     * @throws NonUniqueResultException
     */
    public function testFindByReferenceChunk(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => '01']);

        $signalement = $signalementRepository->findByReferenceChunk(
            $territory,
            '2022-14'
        );

        $this->assertEquals('01', $signalement->getTerritory()->getZip());
    }

    public function testFindWithNoGeolocalisation(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => '13']);
        $signalements = $signalementRepository->findWithNoGeolocalisation($territory);
        $this->assertEmpty($signalements);
        $signalements = $signalementRepository->findWithNoGeolocalisation();
        $this->assertEmpty($signalements);
    }

    public function testSignalementHasRSD(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2024-01']);

        $this->assertTrue($signalement->hasQualificaton(Qualification::RSD));
    }

    public function testSignalementHasNotRSD(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2024-02']);

        $this->assertTrue($signalement->hasQualificaton(Qualification::RSD));
    }

    public function testCountRefused(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalementsRefused = $signalementRepository->countRefused();
        $this->assertEquals(1, $signalementsRefused);
    }

    public function testCountCritereByZone(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $zones = $signalementRepository->countCritereByZone(null, null);
        $this->assertEquals(4, \count($zones));
        $this->assertArrayHasKey('critere_batiment_count', $zones);
        $this->assertArrayHasKey('critere_logement_count', $zones);
        $this->assertArrayHasKey('desordrecritere_batiment_count', $zones);
        $this->assertArrayHasKey('desordrecritere_logement_count', $zones);
    }

    public function testCountByDesordresCriteres(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $desordreCriteres = $signalementRepository->countByDesordresCriteres(null, null, null);
        $this->assertEquals(5, \count($desordreCriteres));
        $this->assertArrayHasKey('count', $desordreCriteres[0]);
        $this->assertArrayHasKey('labelCritere', $desordreCriteres[0]);
    }

    public function testCountSignalementUsagerAbandonProcedure(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalementsUsagerAbandonProcedure = $signalementRepository->countSignalementUsagerAbandonProcedure([]);
        $this->assertEquals(2, $signalementsUsagerAbandonProcedure);
    }

    public function testCountSignalementUsagerAbandonProcedure13(): void
    {
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => '13']);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalementsUsagerAbandonProcedure = $signalementRepository->countSignalementUsagerAbandonProcedure([$territory]);
        $this->assertEquals(1, $signalementsUsagerAbandonProcedure);
    }

    public function testFindAllArchived(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalementsArchived = $signalementRepository->findAllArchived(1, 50, null, null);
        $this->assertEquals(3, \count($signalementsArchived));
    }

    public function testFindAllArchivedTerritory(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => '13']);
        $signalementsArchived = $signalementRepository->findAllArchived(1, 50, $territory, null);
        $this->assertEquals(1, \count($signalementsArchived));
    }

    public function testFindAllArchivedReference(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalementsArchived = $signalementRepository->findAllArchived(1, 50, null, '2024-04');
        $this->assertEquals(1, \count($signalementsArchived));
    }

    public function testFindAllForEmailAndAddressWithValue(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $emailExistingSignalements = $signalementRepository->findAllForEmailAndAddress(
            'admin-partenaire-13-01@signal-logement.fr',
            '3 rue Mars',
            '13015',
            'Marseille',
        );
        $this->assertEquals(1, \count($emailExistingSignalements));
    }

    public function testFindAllForEmailAndAddressWithNullValue(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $emptyEmailExistingSignalements = $signalementRepository->findAllForEmailAndAddress(null, null, null, null);
        $this->assertEmpty($emptyEmailExistingSignalements);
    }

    /**
     * @dataProvider provideSearchWithGeoData
     *
     * @param array<mixed> $options
     */
    public function testfindAllWithGeoData(string $email, array $options, int $nbResult): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalements = $signalementRepository->findAllWithGeoData($user, $options, 0);
        $this->assertCount($nbResult, $signalements);
    }

    public function provideSearchWithGeoData(): \Generator
    {
        yield 'Search all for super admin' => [self::USER_ADMIN, [], 47];
        yield 'Search in Marseille for super admin' => [self::USER_ADMIN, ['cities' => ['Marseille']], 25];
        yield 'Search all for admin partner multi territories' => [self::USER_ADMIN_MULTI_13, [], 6];
        yield 'Search in Ain for admin partner multi territories' => [self::USER_ADMIN_MULTI_13, ['territories' => 1], 1];
        yield 'Search all for user partner multi territories' => [self::USER_AGENT_MULTI_34, [], 2];
        yield 'Search in Hérault for user partner multi territories' => [self::USER_AGENT_MULTI_34, ['territories' => 35], 1];
    }

    public function testfindSignalementsLastSuiviWithSuiviAuto(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => '13']);

        $signalements = $signalementRepository->findSignalementsLastSuiviWithSuiviAuto($territory, 10);
        $this->assertCount(0, $signalements);
    }

    public function testfindSignalementsLastSuiviByPartnerOlderThan(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => '13']);

        $signalements = $signalementRepository->findSignalementsLastSuiviByPartnerOlderThan($territory, 10, 0);
        $this->assertCount(2, $signalements);
    }

    public function testfindOnSameAddress(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['reference' => '2025-09']);

        $signalementsOnSameAddress = $signalementRepository->findOnSameAddress(
            $signalement,
            [],
            [SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED, SignalementStatus::ARCHIVED]
        );
        $this->assertCount(1, $signalementsOnSameAddress);

        $new = new Signalement();
        $new->setAdresseOccupant($signalement->getAdresseOccupant());
        $new->setCpOccupant($signalement->getCpOccupant());
        $new->setVilleOccupant($signalement->getVilleOccupant());

        $signalementsOnSameAddress = $signalementRepository->findOnSameAddress($new);
        $this->assertCount(2, $signalementsOnSameAddress);
    }

    public function testFindNewDossiersFromFormulaireUsager(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        static::getContainer()->get('security.token_storage')->setToken($token);

        $tabQueryParameter = new TabQueryParameters(
            createdFrom: TabDossier::CREATED_FROM_FORMULAIRE_USAGER,
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );

        $dossiers = $signalementRepository->findNewDossiersFrom(
            signalementStatus: SignalementStatus::NEED_VALIDATION,
            tabQueryParameters: $tabQueryParameter,
        );

        foreach ($dossiers as $dossier) {
            $this->assertNotNull($dossier->uuid);
            $this->assertNotNull($dossier->profilDeclarant);
            $this->assertNotNull($dossier->nomDeclarant);
            $this->assertNotNull($dossier->prenomDeclarant);
            $this->assertNotNull($dossier->reference);
            $this->assertNotNull($dossier->adresse);
            $this->assertNotNull($dossier->depotAt);
            $this->assertTrue(in_array($dossier->parc, ['PUBLIC', 'PRIVÉ']));
        }
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function testCountNewDossiersFromFormulaireUsager(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        static::getContainer()->get('security.token_storage')->setToken($token);

        $tabQueryParameter = new TabQueryParameters(
            createdFrom: TabDossier::CREATED_FROM_FORMULAIRE_USAGER,
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );

        $countDossiers = $signalementRepository->countNewDossiersFrom(
            signalementStatus: SignalementStatus::NEED_VALIDATION,
            tabQueryParameters: $tabQueryParameter
        );

        $this->assertTrue(11 === $countDossiers);
    }

    /**
     * @covers \App\Repository\SignalementRepository::countSignalementsSansSuiviPartenaireDepuis60Jours
     * @covers \App\Repository\SignalementRepository::getSignalementsIdSansSuiviPartenaireDepuis60Jours
     * @covers \App\Repository\SignalementRepository::findSignalementsSansSuiviPartenaireDepuis60Jours
     */
    public function testSignalementsSansSuiviPartenaireDepuis60Jours(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => self::USER_PARTNER_TERRITORY_13]);

        $this->assertNotNull($user, 'User partenaire doit exister en base de test');

        // On fabrique des params simples (sans tri ni filtre particulier)
        $params = new TabQueryParameters();
        $params->partners = [];
        $params->mesDossiersAverifier = null;
        $params->queryCommune = null;

        $count = $signalementRepository->countSignalementsSansSuiviPartenaireDepuis60Jours($user, $params);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);

        $ids = $signalementRepository->getSignalementsIdSansSuiviPartenaireDepuis60Jours($user, $params);
        $this->assertIsArray($ids);
        foreach ($ids as $id) {
            $this->assertIsInt($id);
        }

        $results = $signalementRepository->findSignalementsSansSuiviPartenaireDepuis60Jours($user, $params);
        $this->assertIsArray($results);

        foreach ($results as $row) {
            $this->assertArrayHasKey('id', $row);
            $this->assertArrayHasKey('uuid', $row);
            $this->assertArrayHasKey('reference', $row);
            $this->assertArrayHasKey('adresse', $row);
            $this->assertArrayHasKey('dernierSuiviAt', $row);
            $this->assertArrayHasKey('nbJoursDepuisDernierSuivi', $row);
        }
    }

    public function testFindSignalementsSansSuiviPartenaireAvecFiltreCommune(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);

        $user = $userRepository->findOneBy(['email' => self::USER_PARTNER_TERRITORY_13]);
        $params = new TabQueryParameters();
        $params->queryCommune = 'Marseille';

        $results = $signalementRepository->findSignalementsSansSuiviPartenaireDepuis60Jours($user, $params);
        foreach ($results as $row) {
            $this->assertStringContainsStringIgnoringCase('Marseille', $row['adresse']);
        }
    }

    /**
     * @param array<string, mixed> $userConfig
     * @param array<string, mixed> $options
     * @param array<int, string>   $expectedDqlParts
     * @param array<string, mixed> $expectedParams
     *
     * @dataProvider userOptionsProvider
     */
    public function testFindSignalementAffectationQueryBuilder(
        array $userConfig,
        array $options,
        array $expectedDqlParts,
        array $expectedParams,
    ): void {
        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('isUserPartner')->willReturn($userConfig['isUserPartner']);
        $user->method('isPartnerAdmin')->willReturn($userConfig['isPartnerAdmin']);
        $user->method('isTerritoryAdmin')->willReturn($userConfig['isTerritoryAdmin']);
        $user->method('getPartners')->willReturn(new ArrayCollection($userConfig['partners'] ?? []));
        $user->method('getPartnersTerritories')->willReturn($userConfig['territories'] ?? []);

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $qb = $signalementRepository->findSignalementAffectationQueryBuilder($user, $options);

        $this->assertInstanceOf(QueryBuilder::class, $qb);

        $dql = $qb->getDQL();
        $params = $qb->getParameters();

        // Vérifie que les morceaux de DQL attendus sont présents
        foreach ($expectedDqlParts as $part) {
            $this->assertStringContainsString($part, $dql);
        }

        // Vérifie que les paramètres sont bien définis
        $getParamValue = fn (string $name) => array_reduce(
            $params->toArray(),
            fn ($carry, $param) => $param->getName() === $name ? $param->getValue() : $carry,
            null
        );

        foreach ($expectedParams as $paramName => $expectedValue) {
            $value = $getParamValue($paramName);
            $this->assertNotNull($value, "Param $paramName should exist");
            $this->assertEquals($expectedValue, $value);
        }
    }

    /**
     * @return array<string, array<mixed, mixed>>
     */
    public function userOptionsProvider(): array
    {
        return [
            'Partner user, simple options' => [
                ['isUserPartner' => true, 'isPartnerAdmin' => false, 'isTerritoryAdmin' => false, 'partners' => [], 'territories' => []],
                ['statuses' => [SignalementStatus::ACTIVE->value], 'sortBy' => 'reference', 'orderBy' => 'ASC'],
                ['LEFT JOIN s.affectations', 'LEFT JOIN a.partner', 's.id IN'],
                ['statusList' => [SignalementStatus::ARCHIVED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED]],
            ],
            'Partner admin with bailleur' => [
                ['isUserPartner' => false, 'isPartnerAdmin' => true, 'isTerritoryAdmin' => false, 'partners' => [], 'territories' => []],
                ['bailleurSocial' => 'LOGEMENT1', 'statuses' => [SignalementStatus::ACTIVE->value]],
                ['AND s.bailleur = :bailleur', 'LEFT JOIN s.affectations', 'DISTINCT IDENTITY(a2.signalement)'],
                [
                    'statusList' => [SignalementStatus::ARCHIVED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED],
                    'bailleur' => 'LOGEMENT1',
                    'partners' => new ArrayCollection([]),
                    'statut_affectation' => [SignalementStatus::ACTIVE->mapAffectationStatus()],
                ],
            ],
            'Territory admin with empty territories' => [
                ['isUserPartner' => false, 'isPartnerAdmin' => false, 'isTerritoryAdmin' => true, 'partners' => [], 'territories' => [1, 2]],
                [],
                ['s.territory IN (:territories)'],
                [
                    'statusList' => [SignalementStatus::ARCHIVED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED],
                    'territories' => [1, 2],
                ],
            ],
        ];
    }
}
