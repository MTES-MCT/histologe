<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Repository\DesordreCritereRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DesordreCritereRepositoryTest extends KernelTestCase
{
    private DesordreCritereRepository $desordreCritereRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->desordreCritereRepository = static::getContainer()->get(DesordreCritereRepository::class);
    }

    public function testFindBySlugsWithPrecisions(): void
    {
        $desordresCriteres = $this->desordreCritereRepository->findBySlugsWithPrecisions([
            'desordres_service_secours_logement_inadapte',
            'desordres_service_secours_humidite_moisissures',
            'desordres_service_secours_chauffage_dangereux',
            'desordres_service_secours_nuisibles',
            'desordres_service_secours_parties_communes_degradees',
            'desordres_service_secours_autre',
            'fake_desordre',
        ]);

        $this->assertCount(6, $desordresCriteres);
    }
}
