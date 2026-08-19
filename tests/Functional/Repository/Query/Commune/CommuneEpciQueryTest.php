<?php

namespace App\Tests\Functional\Repository\Query\Commune;

use App\Repository\Query\Commune\CommuneEpciQuery;
use App\Repository\TerritoryRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CommuneEpciQueryTest extends KernelTestCase
{
    public function testEpciCommune(): void
    {
        /** @var CommuneEpciQuery $communeEpciQuery */
        $communeEpciQuery = static::getContainer()->get(CommuneEpciQuery::class);
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = static::getContainer()->get(TerritoryRepository::class);

        $epcis = $communeEpciQuery->findEpciByCommuneTerritory($territoryRepository->find(13));

        $this->assertCount(1, $epcis);
        $this->assertEquals('200054807', $epcis[0]['code']);
        $this->assertEquals('Métropole d\'Aix-Marseille-Provence', $epcis[0]['nom']);
    }
}
