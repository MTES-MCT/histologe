<?php

namespace App\Tests\Functional\Service\Statistics;

use App\Repository\TerritoryRepository;
use App\Service\Statistics\ListCommunesStatisticProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ListCommunesStatisticProviderTest extends KernelTestCase
{
    /**
     * @dataProvider provideCommunesWithArrondissements
     */
    public function testGetData(string $zip, string $commune): void
    {
        self::bootKernel();
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = self::getContainer()->get(TerritoryRepository::class);
        $territory = $territoryRepository->findOneBy(['zip' => $zip]);
        $dataCommunes = (new ListCommunesStatisticProvider())->getData($territory);

        $this->assertArrayHasKey($commune, $dataCommunes);
        foreach ($dataCommunes as $key => $dataCommune) {
            $this->assertStringNotContainsString('Arrondissement', $key);
            $this->assertStringNotContainsString('Arrondissement', $dataCommune);
        }
    }

    private function provideCommunesWithArrondissements(): \Generator
    {
        yield 'Lyon' => ['69', 'Lyon'];
        yield 'Marseille' => ['13', 'Marseille'];
        yield 'Paris' => ['75', 'Paris'];
    }
}
