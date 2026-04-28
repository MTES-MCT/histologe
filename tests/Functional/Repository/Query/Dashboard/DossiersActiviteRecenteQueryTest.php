<?php

namespace App\Tests\Functional\Repository\Query\Dashboard;

use App\Entity\User;
use App\Repository\Query\Dashboard\DossiersActiviteRecenteQuery;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DossiersActiviteRecenteQueryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DossiersActiviteRecenteQuery $dossiersActiviteRecenteQuery;
    public const USER_ADMIN = 'admin-01@signal-logement.fr';

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
        $this->dossiersActiviteRecenteQuery = static::getContainer()->get(DossiersActiviteRecenteQuery::class);
    }

    public function testFindLastSignalementsWithOtherUserSuivi(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $tabQueryParameter = new TabQueryParameters(
            mesDossiersActiviteRecente: '1',
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );
        $result = $this->dossiersActiviteRecenteQuery->findLastSignalementsWithOtherUserSuivi($user, $tabQueryParameter);
        $this->assertIsArray($result);
    }

    public function testFindPaginatedLastSignalementsWithUserSuivi(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $territory = null;
        $page = 1;
        $maxResult = 5;

        $paginator = $this->dossiersActiviteRecenteQuery
            ->findPaginatedLastSignalementsWithUserSuivi($user, $territory, $page, $maxResult);

        $rows = iterator_to_array($paginator);
        if (count($rows) > 0) {
            $row = $rows[0];
            $this->assertArrayHasKey('reference', $row);
            $this->assertArrayHasKey('nomOccupant', $row);
            $this->assertArrayHasKey('prenomOccupant', $row);
            $this->assertArrayHasKey('adresseOccupant', $row);
            $this->assertArrayHasKey('uuid', $row);
            $this->assertArrayHasKey('statut', $row);
            $this->assertArrayHasKey('suiviCreatedAt', $row);
            $this->assertArrayHasKey('suiviCategory', $row);
            $this->assertArrayHasKey('suiviIsVisibleForUsager', $row);
            $this->assertArrayHasKey('hasNewerSuivi', $row);
        }
    }

    public function testCountLastSignalementsWithUserSuivi(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $territory = null;

        $count = $this->dossiersActiviteRecenteQuery->countLastSignalementsWithUserSuivi($user, $territory);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);

        $paginator = $this->dossiersActiviteRecenteQuery->findPaginatedLastSignalementsWithUserSuivi(
            $user,
            $territory,
            1,
            1000
        );
        $rows = iterator_to_array($paginator);
        $this->assertSame($count, count($rows));
    }
}
