<?php

namespace App\Tests\Functional\Repository\Query\Dashboard;

use App\Entity\Territory;
use App\Entity\User;
use App\Repository\Query\Dashboard\KpiQuery;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

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
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        static::getContainer()->get('security.token_storage')->setToken($token);

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
        $this->assertEquals(3, $countAll);

        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy([]);
        if ($territory) {
            $countTerritory = $this->kpiQuery->countPartnerInterfaces([$territory->getId()]);
            $this->assertIsInt($countTerritory);
            $this->assertEquals(0, $countTerritory);
        }
    }
}
