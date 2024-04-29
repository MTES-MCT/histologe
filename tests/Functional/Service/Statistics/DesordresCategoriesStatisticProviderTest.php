<?php

namespace App\Tests\Functional\Service\Statistics;

use App\Repository\SignalementRepository;
use App\Service\Statistics\DesordresCategoriesStatisticProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DesordresCategoriesStatisticProviderTest extends KernelTestCase
{
    public function testGetData(): void
    {
        self::bootKernel();
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        $data = (new DesordresCategoriesStatisticProvider($signalementRepository))->getData(null, null);

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
