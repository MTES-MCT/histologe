<?php

namespace App\Tests\Functional\Service\Statistics;

use App\Repository\SignalementRepository;
use App\Service\Statistics\MotifClotureStatisticProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MotifClotureStatisticProviderTest extends KernelTestCase
{
    public function testGetDataDoughnut(): void
    {
        self::bootKernel();
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        $data = (new MotifClotureStatisticProvider($signalementRepository))->getData(null, null);

        $this->assertEquals(14, \count($data));
        $this->assertArrayHasKey('TRAVAUX_FAITS_OU_EN_COURS', $data);
        $this->assertArrayHasKey('color', $data['TRAVAUX_FAITS_OU_EN_COURS']);
        $this->assertArrayHasKey('label', $data['TRAVAUX_FAITS_OU_EN_COURS']);
        $this->assertArrayHasKey('count', $data['TRAVAUX_FAITS_OU_EN_COURS']);
        $this->assertEquals('#18753C', $data['TRAVAUX_FAITS_OU_EN_COURS']['color']);
        $this->assertEquals('Travaux faits ou en cours', $data['TRAVAUX_FAITS_OU_EN_COURS']['label']);
    }

    public function testGetDataBar(): void
    {
        self::bootKernel();
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        $data = (new MotifClotureStatisticProvider($signalementRepository))->getData(null, null, 'bar');
        $this->assertEquals(3, \count($data));
        $this->assertArrayHasKey('Abandon de procédure / absence de réponse', $data);
        $this->assertEquals(1, $data['Abandon de procédure / absence de réponse']);
        $this->assertArrayHasKey('Non décence', $data);
        $this->assertEquals(2, $data['Non décence']);
        $this->assertArrayHasKey('Travaux faits ou en cours', $data);
        $this->assertEquals(1, $data['Travaux faits ou en cours']);
    }
}
