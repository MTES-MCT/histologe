<?php

namespace App\Tests\Functional\Repository\Query\Dashboard;

use App\Entity\Territory;
use App\Entity\User;
use App\Repository\Query\Dashboard\KpiQuery;
use App\Repository\UserRepository;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class KpiQueryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private KpiQuery $kpiQuery;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
        $this->kpiQuery = static::getContainer()->get(KpiQuery::class);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function testCountInjonctions(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);

        $tabQueryParameter = new TabQueryParameters(
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );

        $countInjonctions = $this->kpiQuery->countInjonctions(
            user: $user,
            params: $tabQueryParameter
        );

        $this->assertEquals(2, $countInjonctions);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function testCountInjonctionsNouveauxMessages(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);

        $tabQueryParameter = new TabQueryParameters(
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );

        $countInjonctions = $this->kpiQuery->countInjonctionsNouveauxMessages(
            user: $user,
            params: $tabQueryParameter,
            messageType: 'usager',
        );

        $this->assertEquals(1, $countInjonctions);
    }

    public function testCountPartnerNonNotifiables(): void
    {
        $count = $this->kpiQuery->countPartnerNonNotifiables([]);
        $this->assertEquals(1, $count->getNonNotifiables());

        $count = $this->kpiQuery->countPartnerNonNotifiables([13]);
        $this->assertEquals(1, $count->getNonNotifiables());

        $count = $this->kpiQuery->countPartnerNonNotifiables([1]);
        $this->assertEquals(0, $count->getNonNotifiables());
    }

    public function testCountPartnerInterfaces(): void
    {
        $countAll = $this->kpiQuery->countPartnerInterfaces([]);
        $this->assertIsInt($countAll);
        $this->assertEquals(4, $countAll);

        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy([]);
        if ($territory) {
            $countTerritory = $this->kpiQuery->countPartnerInterfaces([$territory->getId()]);
            $this->assertIsInt($countTerritory);
            $this->assertEquals(0, $countTerritory);
        }
    }
}
