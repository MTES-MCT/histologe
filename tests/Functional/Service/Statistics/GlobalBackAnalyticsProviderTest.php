<?php

namespace App\Tests\Functional\Service\Statistics;

use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Service\Statistics\GlobalBackAnalyticsProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GlobalBackAnalyticsProviderTest extends KernelTestCase
{
    public function testGetData(): void
    {
        self::bootKernel();
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = self::getContainer()->get(TerritoryRepository::class);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        $data = (new GlobalBackAnalyticsProvider($signalementRepository))->getData(null, new ArrayCollection());

        $this->assertEquals(6, \count($data));
        $this->assertArrayHasKey('count_signalement', $data);
        $this->assertEquals(50, $data['count_signalement']);
        $this->assertArrayHasKey('average_criticite', $data);
        $this->assertArrayHasKey('average_days_validation', $data);
        $this->assertArrayHasKey('average_days_closure', $data);
        $this->assertArrayHasKey('count_signalement_archives', $data);
        $this->assertEquals(3, $data['count_signalement_archives']);
        $this->assertArrayHasKey('count_signalement_refuses', $data);
        $this->assertEquals(1, $data['count_signalement_refuses']);
    }
}
