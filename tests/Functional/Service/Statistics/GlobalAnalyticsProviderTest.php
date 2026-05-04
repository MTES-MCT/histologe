<?php

namespace App\Tests\Functional\Service\Statistics;

use App\Service\Statistics\GlobalAnalyticsProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GlobalAnalyticsProviderTest extends KernelTestCase
{
    public function testGetData(): void
    {
        self::bootKernel();
        /** @var GlobalAnalyticsProvider $globalAnalyticsProvider */
        $globalAnalyticsProvider = static::getContainer()->get(GlobalAnalyticsProvider::class);
        $data = $globalAnalyticsProvider->getData();

        $this->assertEquals(7, \count($data));
        $this->assertArrayHasKey('count_signalement_resolus', $data);
        $this->assertArrayHasKey('count_signalement', $data);
        $this->assertArrayHasKey('count_territory', $data);
        $this->assertArrayHasKey('percent_validation', $data);
        $this->assertArrayHasKey('percent_cloture', $data);
        $this->assertArrayHasKey('percent_refused', $data);
        $this->assertArrayHasKey('count_imported', $data);
    }
}
