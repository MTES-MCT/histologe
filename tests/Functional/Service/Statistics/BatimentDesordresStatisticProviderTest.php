<?php

namespace App\Tests\Functional\Service\Statistics;

use App\Repository\Query\Statistics\CountStatisticsQuery;
use App\Service\Statistics\BatimentDesordresStatisticProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BatimentDesordresStatisticProviderTest extends KernelTestCase
{
    public function testGetData(): void
    {
        self::bootKernel();
        /** @var CountStatisticsQuery $countStatisticsQuery */
        $countStatisticsQuery = self::getContainer()->get(CountStatisticsQuery::class);
        $data = (new BatimentDesordresStatisticProvider($countStatisticsQuery))->getData(null, null);

        $this->assertEquals(5, \count($data));
        $this->assertArrayHasKey('label', $data[0]);
        $this->assertArrayHasKey('count', $data[0]);
        $this->assertArrayHasKey('color', $data[0]);
        $this->assertEquals('#2F4077', $data[0]['color']);
    }
}
