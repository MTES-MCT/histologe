<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\AffectationRepository;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\DashboardTabPanel\TabDossier;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class SignalementRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private const string USER_PARTNER_TERRITORY_13 = 'user-13-01@signal-logement.fr';

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
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

    public function testFindAllForEmailAndAddressWithTiersValueDouble(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $emailExistingSignalements = $signalementRepository->findAllForEmailAndAddress(
            'admin-partenaire-13-01@signal-logement.fr',
            '3 rue Mars',
            '13015',
            'Marseille',
            true,
            'Fragione'
        );
        $this->assertEquals(1, \count($emailExistingSignalements));
    }

    public function testFindAllForEmailAndAddressWithTiersValueNoDouble(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $emailExistingSignalements = $signalementRepository->findAllForEmailAndAddress(
            'admin-partenaire-13-01@signal-logement.fr',
            '3 rue Mars',
            '13015',
            'Marseille',
            true,
            'Mussard'
        );
        $this->assertEquals(0, \count($emailExistingSignalements));
    }

    public function testFindAllForEmailAndAddressWithNullValue(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $emptyEmailExistingSignalements = $signalementRepository->findAllForEmailAndAddress(null, null, null, null);
        $this->assertEmpty($emptyEmailExistingSignalements);
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
        $this->assertCount(1, $signalements);
    }

    public function testfindOnSameAddress(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['reference' => '2025-09']);

        $signalementsOnSameAddress = $signalementRepository->findOnSameAddress(
            $signalement,
            [],
            SignalementStatus::excludedStatuses(),
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
    public function testCountInjonctions(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        static::getContainer()->get('security.token_storage')->setToken($token);

        $tabQueryParameter = new TabQueryParameters(
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );

        $countInjonctions = $signalementRepository->countInjonctions(
            user: $user,
            params: $tabQueryParameter
        );

        $this->assertEquals(2, $countInjonctions);
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
        $this->assertEquals(8, $countDossiers);
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
        $this->assertIsArray($results);
        foreach ($results as $row) {
            $this->assertStringContainsStringIgnoringCase('Marseille', $row['adresse']);
        }
    }

    public function testFindOneForLoginBailleur(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        $referenceInjonction = 'INJ-2364';
        $loginBailleur = 'XXXX-XXXX-XXXX-XXXX';
        $signalement = $signalementRepository->findOneForLoginBailleur($referenceInjonction, $loginBailleur);
        $this->assertInstanceOf(Signalement::class, $signalement);

        $referenceInjonction = 'inj-2364';
        $loginBailleur = 'XXXX-XXXX-XXXX-XXXX';
        $signalement = $signalementRepository->findOneForLoginBailleur($referenceInjonction, $loginBailleur);
        $this->assertInstanceOf(Signalement::class, $signalement);

        $referenceInjonction = 'PLOP-2364';
        $loginBailleur = 'XXXX-XXXX-XXXX-XXXX';
        $signalement = $signalementRepository->findOneForLoginBailleur($referenceInjonction, $loginBailleur);
        $this->assertNull($signalement);
    }

    public function testGetActiveSignalementsForUserRT(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);

        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@signal-logement.fr']);

        $count = $signalementRepository->getActiveSignalementsForUser($user, true);
        $expected = \count($signalementRepository->findBy(['territory' => 13, 'statut' => SignalementStatus::ACTIVE]));
        $this->assertEquals($expected, $count);
    }

    public function testGetActiveSignalementsForUserAgent(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var AffectationRepository $affectationRepository */
        $affectationRepository = $this->entityManager->getRepository(Affectation::class);

        $user = $userRepository->findOneBy(['email' => 'admin-partenaire-13-01@signal-logement.fr']);

        $count = $signalementRepository->getActiveSignalementsForUser($user, true);
        $affectations = $affectationRepository->findBy(['partner' => $user->getPartners()->first(), 'statut' => AffectationStatus::ACCEPTED]);

        $this->assertEquals(\count($affectations), $count);
    }

    public function testGetActiveSignalementsWithInteractionsForUserRT(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var SuiviRepository $suiviRepository */
        $suiviRepository = $this->entityManager->getRepository(Suivi::class);
        /** @var AffectationRepository $affectationRepository */
        $affectationRepository = $this->entityManager->getRepository(Affectation::class);

        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@signal-logement.fr']);

        $count = $signalementRepository->getActiveSignalementsWithInteractionsForUser($user, true);
        $suivis = $suiviRepository->findBy(['createdBy' => $user]);
        $signaleementsIds = [];
        foreach ($suivis as $suivi) {
            if (SignalementStatus::ACTIVE === $suivi->getSignalement()->getStatut()) {
                $signaleementsIds[$suivi->getSignalement()->getId()] = $suivi->getSignalement()->getId();
            }
        }
        $affectation = $affectationRepository->findBy(['statut' => AffectationStatus::ACCEPTED, 'answeredBy' => $user]);
        foreach ($affectation as $aff) {
            if (SignalementStatus::ACTIVE === $aff->getSignalement()->getStatut()) {
                $signaleementsIds[$aff->getSignalement()->getId()] = $aff->getSignalement()->getId();
            }
        }

        $this->assertEquals(\count($signaleementsIds), $count);
    }

    public function testGetActiveSignalementsWithInteractionsForUserAgent(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var SuiviRepository $suiviRepository */
        $suiviRepository = $this->entityManager->getRepository(Suivi::class);
        /** @var AffectationRepository $affectationRepository */
        $affectationRepository = $this->entityManager->getRepository(Affectation::class);

        $user = $userRepository->findOneBy(['email' => 'admin-partenaire-13-01@signal-logement.fr']);

        $count = $signalementRepository->getActiveSignalementsWithInteractionsForUser($user, true);
        $suivis = $suiviRepository->findBy(['createdBy' => $user]);
        $signaleementsIds = [];
        foreach ($suivis as $suivi) {
            if (SignalementStatus::ACTIVE === $suivi->getSignalement()->getStatut()) {
                $signaleementsIds[$suivi->getSignalement()->getId()] = $suivi->getSignalement()->getId();
            }
        }
        $affectation = $affectationRepository->findBy(['statut' => AffectationStatus::ACCEPTED, 'answeredBy' => $user]);
        foreach ($affectation as $aff) {
            if (SignalementStatus::ACTIVE === $aff->getSignalement()->getStatut()) {
                $signaleementsIds[$aff->getSignalement()->getId()] = $aff->getSignalement()->getId();
            }
        }

        $this->assertEquals(\count($signaleementsIds), $count);
    }

    public function testFindInjonctionBeforePeriodWithoutAnswer(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        $signalements = $signalementRepository->findInjonctionBeforePeriodWithoutAnswer('3 weeks');
        $this->assertCount(0, $signalements);

        $referenceInjonction = '2363';
        $signalement = $signalementRepository->findOneBy(['referenceInjonction' => $referenceInjonction]);
        $signalement->setCreatedAt(new \DateTimeImmutable('-2 months'));
        $this->entityManager->flush();

        $signalements = $signalementRepository->findInjonctionBeforePeriodWithoutAnswer('3 weeks');
        $this->assertCount(1, $signalements);
    }
}
