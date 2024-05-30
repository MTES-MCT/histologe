<?php

namespace App\Tests\Functional\Repository;

use App\Repository\CommuneRepository;
use App\Repository\TerritoryRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CommuneRepositoryTest extends KernelTestCase
{
    public function testEpciCommune(): void
    {
        /** @var CommuneRepository $commmuneRepository */
        $commmuneRepository = static::getContainer()->get(CommuneRepository::class);
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = static::getContainer()->get(TerritoryRepository::class);

        $epcis = $commmuneRepository->findEpciByCommuneTerritory($territoryRepository->find(13));

        $this->assertCount(1, $epcis);
        $this->assertEquals('200054807', $epcis[0]['code']);
        $this->assertEquals('Métropole d\'Aix-Marseille-Provence', $epcis[0]['nom']);
    }
}
