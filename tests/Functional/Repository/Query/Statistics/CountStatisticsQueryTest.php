<?php

namespace App\Tests\Functional\Repository\Query\Statistics;

use App\Entity\Territory;
use App\Repository\Query\Statistics\CountStatisticsQuery;
use App\Repository\TerritoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CountStatisticsQueryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private CountStatisticsQuery $countStatisticsQuery;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
        $this->countStatisticsQuery = static::getContainer()->get(CountStatisticsQuery::class);
    }

    public function testCountRefused(): void
    {
        $signalementsRefused = $this->countStatisticsQuery->countRefused();
        $this->assertEquals(1, $signalementsRefused);
    }

    public function testCountCritereByZone(): void
    {
        $zones = $this->countStatisticsQuery->countCritereByZone(null, null);
        $this->assertEquals(4, \count($zones));
        $this->assertArrayHasKey('critere_batiment_count', $zones);
        $this->assertArrayHasKey('critere_logement_count', $zones);
        $this->assertArrayHasKey('desordrecritere_batiment_count', $zones);
        $this->assertArrayHasKey('desordrecritere_logement_count', $zones);
    }

    public function testCountByDesordresCriteres(): void
    {
        $desordreCriteres = $this->countStatisticsQuery->countByDesordresCriteres(null, null, null);
        $this->assertEquals(5, \count($desordreCriteres));
        $this->assertArrayHasKey('count', $desordreCriteres[0]);
        $this->assertArrayHasKey('labelCritere', $desordreCriteres[0]);
    }

    public function testCountSignalementUsagerAbandonProcedure(): void
    {
        $signalementsUsagerAbandonProcedure = $this->countStatisticsQuery->countSignalementUsagerAbandonProcedure([]);
        $this->assertEquals(2, $signalementsUsagerAbandonProcedure);
    }

    public function testCountSignalementUsagerAbandonProcedure13(): void
    {
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => '13']);
        $signalementsUsagerAbandonProcedure = $this->countStatisticsQuery->countSignalementUsagerAbandonProcedure([$territory]);
        $this->assertEquals(1, $signalementsUsagerAbandonProcedure);
    }
}
