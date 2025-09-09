<?php

namespace App\Tests\Functional\Repository;

use App\Repository\BailleurRepository;
use App\Repository\TerritoryRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BailleurRepositoryTest extends KernelTestCase
{
    public function testFindOneBailleur(): void
    {
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $territory = $territoryRepository->findOneBy(['zip' => '13']);
        /** @var BailleurRepository $bailleurRepository */
        $bailleurRepository = static::getContainer()->get(BailleurRepository::class);

        $bailleur = $bailleurRepository->findOneBailleurBy(
            name: '13 HABITAT',
            territory: $territory,
            bailleurSanitized: true
        );

        $this->assertEquals('13 HABITAT', $bailleur?->getName());
    }

    public function testFindOneBailleurRadie(): void
    {
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $territory = $territoryRepository->findOneBy(['zip' => '13']);
        /** @var BailleurRepository $bailleurRepository */
        $bailleurRepository = static::getContainer()->get(BailleurRepository::class);

        $bailleur = $bailleurRepository->findOneBailleurBy(
            name: 'S.A. REGIONALE DE L\'HABITAT',
            territory: $territory,
            bailleurSanitized: true
        );

        $this->assertEquals("[Radié(e)] S.A. REGIONALE DE L'HABITAT", $bailleur?->getName());
    }
}
