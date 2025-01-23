<?php

namespace App\Tests\Functional\Repository;

use App\Repository\ZoneRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ZoneRepositoryTest extends KernelTestCase
{
    private ZoneRepository $zoneRepository;

    protected function setUp(): void
    {
        $this->zoneRepository = static::getContainer()->get(ZoneRepository::class);
    }

    public function testCountSignalementNoSuiviAfter3Relances(): void
    {
        $zone = $this->zoneRepository->findOneBy(['name' => 'Permis louer Agde']);

        $result = $this->zoneRepository->findSignalementsByZone($zone);
        $this->assertCount(1, $result);
    }
}
