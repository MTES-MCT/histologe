<?php

namespace App\Tests\Functional\Service\Statistics;

use App\Repository\TerritoryRepository;
use App\Service\Statistics\ListCommunesStatisticProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ListCommunesStatisticProviderTest extends KernelTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideCommunesWithArrondissements')]
    public function testGetData(string $zip, string $commune): void
    {
        self::bootKernel();
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $territory = $territoryRepository->findOneBy(['zip' => $zip]);
        $dataCommunes = (new ListCommunesStatisticProvider())->getData($territory);

        $this->assertArrayHasKey($commune, $dataCommunes);
        foreach ($dataCommunes as $key => $dataCommune) {
            $this->assertStringNotContainsString('Arrondissement', $key);
            $this->assertStringNotContainsString('Arrondissement', $dataCommune);
        }
    }

    public static function provideCommunesWithArrondissements(): \Generator
    {
        yield 'Lyon' => ['69A', 'Lyon'];
        yield 'Marseille' => ['13', 'Marseille'];
    }
}
