<?php

namespace App\Tests\Functional\Service\Statistics;

use App\Repository\Query\Statistics\CountStatisticsQuery;
use App\Service\Statistics\DesordresCategoriesStatisticProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DesordresCategoriesStatisticProviderTest extends KernelTestCase
{
    public function testGetData(): void
    {
        self::bootKernel();
        /** @var CountStatisticsQuery $countStatisticsQuery */
        $countStatisticsQuery = self::getContainer()->get(CountStatisticsQuery::class);
        $data = (new DesordresCategoriesStatisticProvider($countStatisticsQuery))->getData(null, null);

        $this->assertEquals(2, \count($data));
        $this->assertArrayHasKey('BATIMENT', $data);
        $this->assertArrayHasKey('LOGEMENT', $data);
        $this->assertArrayHasKey('color', $data['BATIMENT']);
        $this->assertArrayHasKey('label', $data['BATIMENT']);
        $this->assertArrayHasKey('count', $data['BATIMENT']);
        $this->assertEquals('#2F4077', $data['BATIMENT']['color']);
        $this->assertEquals('Bâtiment', $data['BATIMENT']['label']);
        $this->assertEquals('#447049', $data['LOGEMENT']['color']);
        $this->assertEquals('Logement', $data['LOGEMENT']['label']);
    }
}
