<?php

namespace App\Tests\Functional\Repository\Query\Statistics;

use App\Repository\Query\Statistics\CountStatisticsQuery;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CountStatisticsQueryTest extends KernelTestCase
{
    private CountStatisticsQuery $countStatisticsQuery;

    protected function setUp(): void
    {
        $this->countStatisticsQuery = static::getContainer()->get(CountStatisticsQuery::class);
    }

    public function testCountRefused(): void
    {
        $signalementsRefused = $this->countStatisticsQuery->countRefused(true);
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
}
