<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Service\ListFilters\SearchPartner;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PartnerRepositoryTest extends KernelTestCase
{
    public const string USER_ADMIN_TERRITORY_13 = 'admin-territoire-13-01@signal-logement.fr';
    private EntityManagerInterface $entityManager;
    private PartnerRepository $partnerRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
        $this->partnerRepository = $this->entityManager->getRepository(Partner::class);
    }

    public function testFindPartnersAffected(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2022-1']);

        $partners = $this->partnerRepository->findByLocalization($signalement, true);
        $this->assertCount(1, $partners);
    }

    public function testFindPartnersNotAffected(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2022-1']);

        $partners = $this->partnerRepository->findByLocalization($signalement, false);
        $this->assertCount(9, $partners);
    }

    public function testFindPossiblePartnersForCOR69(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-2']);

        $partners = $this->partnerRepository->findByLocalization($signalement, false);
        $this->assertCount(3, $partners);

        $partnerCOR = array_filter($partners, function ($partner) {
            return 'COR' === $partner['name'];
        });
        $this->assertCount(1, $partnerCOR);
    }

    public function testFindPossiblePartnersForMDL69(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-3']);

        $partners = $this->partnerRepository->findByLocalization($signalement, false);
        $this->assertCount(1, $partners);

        $partnerMDL = array_filter($partners, function ($partner) {
            return 'EMHA - Métropole de Lyon' === $partner['name'];
        });
        $this->assertCount(1, $partnerMDL);
    }

    public function testFindPartnersAffectableZoneAndInsee(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2024-09']);

        $partners = $this->partnerRepository->findByLocalization($signalement, false);
        $this->assertCount(7, $partners);

        // partenaires définis par zone
        $partnerZone = array_filter($partners, function ($partner) {
            return 'Cocoland' === $partner['name'] || 'Tiers-Lieu' === $partner['name'];
        });
        $this->assertCount(2, $partnerZone);

        // partenaires définis par code insee
        $partnerInsee = array_filter($partners, function ($partner) {
            return 'Mairie de Saint-Mars du Désert' === $partner['name'] || 'Partner Habitat 44' === $partner['name'];
        });

        // partenaires sans codes insee ni zone (donc sur tout le territoire)
        $partnerGeneraux = array_filter($partners, function ($partner) {
            return 'DDT Loire-Atlantique' === $partner['name'] || 'SDIS 44' === $partner['name'];
        });
        $this->assertCount(2, $partnerGeneraux);

        // partenaire hors territoire
        $partnerAilleurs = array_filter($partners, function ($partner) {
            return 'Partenaire Zone Agde' === $partner['name'];
        });
        $this->assertCount(0, $partnerAilleurs);
    }

    public function testFindPartnersAffectableZone(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2024-08']);

        $partners = $this->partnerRepository->findByLocalization($signalement, false);
        $this->assertCount(5, $partners);

        $partnerZone = array_filter($partners, function ($partner) {
            return 'Partenaire Zone Agde' === $partner['name'];
        });
        $this->assertCount(1, $partnerZone);
    }

    public function testFindPartnersNotAffectableZone(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2024-06']);

        $partners = $this->partnerRepository->findByLocalization($signalement, false);
        $this->assertCount(6, $partners);

        $partnerZone = array_filter($partners, function ($partner) {
            return 'Partenaire Zone Agde' === $partner['name'];
        });
        $this->assertCount(0, $partnerZone);
    }

    public function testFindPartnersInjonctionBailleur(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000012']);

        $partners = $this->partnerRepository->findByLocalization($signalement, false, true);
        $this->assertCount(0, $partners);

        $partners = $this->partnerRepository->findByLocalization($signalement, true, true);
        $this->assertCount(1, $partners);
    }

    public function testGetPartnerPaginator(): void
    {
        $user = new User();
        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => '69']);
        $searchPartner = new SearchPartner($user);
        $searchPartner->setTerritoire($territory);
        $partnerPaginator = $this->partnerRepository->getPartners(1, $searchPartner);

        $this->assertGreaterThan(1, $partnerPaginator->count());
    }

    public function testGetPartnerPaginatorWithSearchPartnerNotNotifiable(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => self::USER_ADMIN_TERRITORY_13]);
        $searchPartner = new SearchPartner($user);
        $searchPartner->setIsNotNotifiable(true);
        $searchPartner->setIsOnlyInterconnected(null);
        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => '13']);
        $searchPartner->setTerritoire($territory);
        $partnerPaginator = $this->partnerRepository->getPartners(50, $searchPartner);
        /** @var array{0: Partner} $partner */
        foreach ($partnerPaginator as $partner) {
            $this->assertFalse($partner[0]->receiveEmailNotifications());
        }

        $this->assertEquals(1, $partnerPaginator->count());
    }

    public function testGetPartnerPaginatorWithSearchPartnerInterconnected(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => self::USER_ADMIN_TERRITORY_13]);
        $searchPartner = new SearchPartner($user);
        $searchPartner->setIsNotNotifiable(false);
        $searchPartner->setIsOnlyInterconnected(true);
        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => '13']);
        $searchPartner->setTerritoire($territory);
        $partnerPaginator = $this->partnerRepository->getPartners(50, $searchPartner);
        $this->assertEquals(3, $partnerPaginator->count());
    }

    public function testGetPartnerPaginatorWithSearchPartnerNotInterconnected(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => self::USER_ADMIN_TERRITORY_13]);
        $searchPartner = new SearchPartner($user);
        $searchPartner->setIsNotNotifiable(false);
        $searchPartner->setIsOnlyInterconnected(false);
        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => '13']);
        $searchPartner->setTerritoire($territory);
        $partnerPaginator = $this->partnerRepository->getPartners(50, $searchPartner);
        $this->assertEquals(7, $partnerPaginator->count());
    }

    public function testGetPartnerQueryBuilder(): void
    {
        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => '69']);
        /** @var QueryBuilder $partnersQueryBuilder */
        $partnersQueryBuilder = $this->partnerRepository->getPartnersQueryBuilder($territory);
        $partners = $partnersQueryBuilder->getQuery()->getResult();
        /** @var Partner $partner */
        foreach ($partners as $partner) {
            $this->assertEquals('69', $partner->getTerritory()->getZip());
        }

        $this->assertGreaterThan(1, $partnersQueryBuilder->getQuery()->getResult());
    }

    public function testCountPartnerNonNotifiables(): void
    {
        $count = $this->partnerRepository->countPartnerNonNotifiables([]);
        $this->assertEquals(1, $count->getNonNotifiables());

        $count = $this->partnerRepository->countPartnerNonNotifiables([13]);
        $this->assertEquals(1, $count->getNonNotifiables());

        $count = $this->partnerRepository->countPartnerNonNotifiables([1]);
        $this->assertEquals(0, $count->getNonNotifiables());
    }

    public function testCountPartnerInterfaces(): void
    {
        $countAll = $this->partnerRepository->countPartnerInterfaces([]);
        $this->assertIsInt($countAll);
        $this->assertEquals(3, $countAll);

        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy([]);
        if ($territory) {
            $countTerritory = $this->partnerRepository->countPartnerInterfaces([$territory->getId()]);
            $this->assertIsInt($countTerritory);
            $this->assertEquals(0, $countTerritory);
        }
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();
    }
}
