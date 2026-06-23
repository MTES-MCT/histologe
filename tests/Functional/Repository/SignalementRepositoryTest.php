<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\AffectationRepository;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\ListFilters\SearchSignalementInjonction;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class SignalementRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

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

    public function testFindInjonctionBeforeDateWithoutAnswer(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        $beforeDate = new \DateTimeImmutable('-3 weeks');
        $signalements = $signalementRepository->findInjonctionBeforeDateWithoutAnswer($beforeDate);
        $this->assertCount(0, $signalements);

        $container = static::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable('+1 month'));
        $container->set(ClockInterface::class, $mockClock);

        $beforeDate = $mockClock->now()->modify('-3 weeks');
        $signalements = $signalementRepository->findInjonctionBeforeDateWithoutAnswer($beforeDate);
        $this->assertCount(1, $signalements);
    }

    public function testFindInjonctionToRemindAnswerBailleur(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        $beforeDate = new \DateTimeImmutable('-16 days');
        $signalements = $signalementRepository->findInjonctionToRemindAnswerBailleur($beforeDate);
        $this->assertCount(0, $signalements);

        $container = static::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable('+1 month'));
        $container->set(ClockInterface::class, $mockClock);

        $beforeDate = $mockClock->now();
        $signalements = $signalementRepository->findInjonctionToRemindAnswerBailleur($beforeDate);
        $this->assertCount(1, $signalements);
    }

    public function testFindInjonctionToRemindBailleur(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        $beforeDate = new \DateTimeImmutable('-1 month');
        $signalements = $signalementRepository->findInjonctionToRemind($beforeDate, 'bailleur');
        $this->assertCount(0, $signalements);

        $container = static::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable('+1 month'));
        $container->set(ClockInterface::class, $mockClock);

        $beforeDate = $mockClock->now()->modify('-1 month');
        $signalements = $signalementRepository->findInjonctionBeforeDateWithoutAnswer($beforeDate);
        $this->assertCount(1, $signalements);
    }

    public function testFindInjonctionToRemindUsager(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        $beforeDate = new \DateTimeImmutable('-1 month');
        $signalements = $signalementRepository->findInjonctionToRemind($beforeDate, 'usager');
        $this->assertCount(0, $signalements);

        $container = static::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable('+1 month'));
        $container->set(ClockInterface::class, $mockClock);

        $beforeDate = $mockClock->now()->modify('-1 month');
        $signalements = $signalementRepository->findInjonctionBeforeDateWithoutAnswer($beforeDate);
        $this->assertCount(1, $signalements);
    }

    public function testFindInjonctionToRemindBailleurWithMessage(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        // Vérification initiale : le suivi bailleur fixture récent de 2025-000000000012 bloque (beforeDate=now-1mois)
        $beforeDate = new \DateTimeImmutable('-1 month');
        $signalements = $signalementRepository->findInjonctionToRemind($beforeDate, 'bailleur');
        $this->assertCount(0, $signalements);

        // MockClock doit être set avant flush() pour éviter l'initialisation de ClockInterface via EntityHistoryListener
        $container = static::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable('+1 month'));
        $container->set(ClockInterface::class, $mockClock);

        // À beforeDate=now, le suivi bailleur fixture de 2025-000000000012 (créé avant now) ne bloque plus → 1 rappel
        $beforeDate = $mockClock->now()->modify('-1 month'); // = now
        $signalements = $signalementRepository->findInjonctionToRemind($beforeDate, 'bailleur');
        $this->assertCount(1, $signalements);

        // Le bailleur de 2025-000000000012 envoie un suivi à +2 semaines : le timer repart de cette date
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000012']);

        $suivi = (new Suivi())
            ->setSignalement($signalement)
            ->setDescription('On a tout réparé !')
            ->setCategory(SuiviCategory::MESSAGE_BAILLEUR)
            ->setIsVisibleForBailleur(true)
            ->setType(SuiviCategory::getSuiviTypeForSuiviCategory(SuiviCategory::MESSAGE_BAILLEUR))
            ->setCreatedAt(new \DateTimeImmutable('+2 weeks'));
        $this->entityManager->persist($suivi);
        $this->entityManager->flush();

        // À beforeDate=now : le nouveau suivi bailleur (à +2 semaines) est plus récent → pas de rappel
        $signalements = $signalementRepository->findInjonctionToRemind($beforeDate, 'bailleur');
        $this->assertCount(0, $signalements);

        // À +7 semaines : beforeDate = +3 semaines, suivi à +2 semaines est antérieur → rappel attendu
        $beforeDate = (new \DateTimeImmutable('+7 weeks'))->modify('-1 month');
        $signalements = $signalementRepository->findInjonctionToRemind($beforeDate, 'bailleur');
        $this->assertCount(1, $signalements);
    }

    public function testFindInjonctionToRemindUsagerWithMessage(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        // Vérification initiale : le suivi fixture récent de 2025-000000000012 bloque (beforeDate=now-1mois)
        $beforeDate = new \DateTimeImmutable('-1 month');
        $signalements = $signalementRepository->findInjonctionToRemind($beforeDate, 'usager');
        $this->assertCount(0, $signalements);

        // MockClock doit être set avant flush() pour éviter l'initialisation de ClockInterface via EntityHistoryListener
        $container = static::getContainer();
        $mockClock = new MockClock(new \DateTimeImmutable('+1 month'));
        $container->set(ClockInterface::class, $mockClock);

        // À beforeDate=now, le suivi fixture de 2025-000000000012 (créé avant now) ne bloque plus → 1 rappel attendu
        $beforeDate = $mockClock->now()->modify('-1 month'); // = now
        $signalements = $signalementRepository->findInjonctionToRemind($beforeDate, 'usager');
        $this->assertCount(1, $signalements);

        // L'usager de 2025-000000000012 envoie un suivi à +2 semaines : le timer repart de cette date
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000012']);

        $suivi = (new Suivi())
            ->setSignalement($signalement)
            ->setDescription('Les travaux avancent bien.')
            ->setCategory(SuiviCategory::MESSAGE_USAGER)
            ->setType(Suivi::TYPE_USAGER)
            ->setCreatedAt(new \DateTimeImmutable('+2 weeks'));
        $this->entityManager->persist($suivi);
        $this->entityManager->flush();

        // À beforeDate=now : le nouveau suivi usager (à +2 semaines) est plus récent → pas de rappel
        $signalements = $signalementRepository->findInjonctionToRemind($beforeDate, 'usager');
        $this->assertCount(0, $signalements);

        // À +7 semaines : beforeDate = +3 semaines, suivi à +2 semaines est antérieur → rappel attendu
        $beforeDate = (new \DateTimeImmutable('+7 weeks'))->modify('-1 month');
        $signalements = $signalementRepository->findInjonctionToRemind($beforeDate, 'usager');
        $this->assertCount(1, $signalements);
    }

    public function testFindInjonctionFilteredPaginatedReturnsOnlyInjonctionStatuses(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        static::getContainer()->get('security.token_storage')->setToken($token);

        /** @var SignalementRepository $repository */
        $repository = $this->entityManager->getRepository(Signalement::class);

        $search = new SearchSignalementInjonction($user);

        $paginator = $repository->findInjonctionFilteredPaginated(
            $search,
            10,
            null
        );

        $this->assertEquals(3, $paginator->count());
        foreach ($paginator as $signalement) {
            $this->assertContains(
                $signalement->getStatut(),
                [
                    SignalementStatus::INJONCTION_BAILLEUR,
                    SignalementStatus::INJONCTION_CLOSED,
                ]
            );
        }
    }

    public function testFindInjonctionFilteredPaginatedWithTerritory(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        static::getContainer()->get('security.token_storage')->setToken($token);

        /** @var SignalementRepository $repository */
        $repository = $this->entityManager->getRepository(Signalement::class);
        $territory = $this->entityManager
            ->getRepository(Territory::class)
            ->findOneBy(['zip' => '34']);

        $search = new SearchSignalementInjonction($user);
        $search->setTerritoire($territory);

        $paginator = $repository->findInjonctionFilteredPaginated(
            $search,
            10,
            null
        );

        $this->assertEquals(2, $paginator->count());
        foreach ($paginator as $signalement) {
            $this->assertEquals('34', $signalement->getTerritory()->getZip());
        }
    }

    public function testFindInjonctionFilteredPaginatedWithNoReponseBailleur(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        static::getContainer()->get('security.token_storage')->setToken($token);
        /** @var SignalementRepository $repository */
        $repository = $this->entityManager->getRepository(Signalement::class);

        $search = new SearchSignalementInjonction($user);
        $search->setReponseBailleur('aucune');

        $paginator = $repository->findInjonctionFilteredPaginated(
            $search,
            10,
            null
        );
        $this->assertEquals(2, $paginator->count());
    }

    public function testFindInjonctionFilteredPaginatedWithStatut(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        static::getContainer()->get('security.token_storage')->setToken($token);
        /** @var SignalementRepository $repository */
        $repository = $this->entityManager->getRepository(Signalement::class);

        $search = new SearchSignalementInjonction($user);
        $search->setStatutSignalement('INJONCTION_CLOSED');

        $paginator = $repository->findInjonctionFilteredPaginated(
            $search,
            10,
            null
        );
        $this->assertEquals(1, $paginator->count());
    }

    public function testFindInjonctionFilteredPaginatedWithMessageUsager(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        /** @var SignalementRepository $repository */
        $repository = $this->entityManager->getRepository(Signalement::class);

        $search = new SearchSignalementInjonction($user);
        $search->setMessages('usager');

        $paginator = $repository->findInjonctionFilteredPaginated(
            $search,
            10,
            null
        );
        $this->assertEquals(1, $paginator->count());
    }
}
