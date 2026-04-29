<?php

namespace App\Tests\Functional\Service\Statistics;

use App\Repository\Query\Statistics\GlobalStatisticsQuery;
use App\Service\Statistics\GlobalBackAnalyticsProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GlobalBackAnalyticsProviderTest extends KernelTestCase
{
    public function testGetData(): void
    {
        self::bootKernel();
        /** @var GlobalStatisticsQuery $globalStatisticsQuery */
        $globalStatisticsQuery = static::getContainer()->get(GlobalStatisticsQuery::class);
        $data = (new GlobalBackAnalyticsProvider($globalStatisticsQuery))->getData(null, new ArrayCollection());

        $this->assertEquals(6, \count($data));
        $this->assertArrayHasKey('count_signalement', $data);
        $this->assertEquals(52, $data['count_signalement']);
        $this->assertArrayHasKey('average_criticite', $data);
        $this->assertArrayHasKey('average_days_validation', $data);
        $this->assertArrayHasKey('average_days_closure', $data);
        $this->assertArrayHasKey('count_signalement_archives', $data);
        $this->assertEquals(3, $data['count_signalement_archives']);
        $this->assertArrayHasKey('count_signalement_refuses', $data);
        $this->assertEquals(1, $data['count_signalement_refuses']);
    }
}
