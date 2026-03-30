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

    public function testFindLastSignalementsWithUserSuivi(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $territory = null;
        $limit = 5;
        $result = $this->dossiersActiviteRecenteQuery->findLastSignalementsWithUserSuivi($user, $territory, $limit);
        $this->assertIsArray($result);
        $this->assertLessThanOrEqual($limit, count($result));
        if (count($result) > 0) {
            $row = $result[0];
            $this->assertArrayHasKey('reference', $row);
            $this->assertArrayHasKey('nomOccupant', $row);
            $this->assertArrayHasKey('prenomOccupant', $row);
            $this->assertArrayHasKey('adresseOccupant', $row);
            $this->assertArrayHasKey('uuid', $row);
            $this->assertArrayHasKey('statut', $row);
            $this->assertArrayHasKey('suiviCreatedAt', $row);
            $this->assertArrayHasKey('suiviCategory', $row);
            $this->assertArrayHasKey('suiviIsPublic', $row);
            $this->assertArrayHasKey('hasNewerSuivi', $row);
        }
    }
}
