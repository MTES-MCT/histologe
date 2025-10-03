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

    public function testFindSignalementsByZone(): void
    {
        $zone = $this->zoneRepository->findOneBy(['name' => 'Permis louer Agde']);
        $this->assertNotNull($zone, 'La zone "Permis louer Agde" doit exister dans les fixtures');

        $result = $this->zoneRepository->findSignalementsByZone($zone);

        $this->assertIsArray($result);
        $this->assertCount(1, $result, 'La zone doit contenir exactement un signalement');
        $this->assertNotEmpty($result, 'La zone doit retourner au moins un signalement');

        $first = $result[0];

        $this->assertArrayHasKey('uuid', $first);
        $this->assertArrayHasKey('reference', $first);
        $this->assertArrayHasKey('geoloc', $first);
        $this->assertArrayHasKey('adresse_occupant', $first);
        $this->assertArrayHasKey('cp_occupant', $first);
        $this->assertArrayHasKey('ville_occupant', $first);

        $this->assertIsArray($first['geoloc']);
        $this->assertArrayHasKey('lat', $first['geoloc']);
        $this->assertArrayHasKey('lng', $first['geoloc']);
    }
}
