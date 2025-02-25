<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Territory;
use App\Repository\BailleurRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BailleurRepositoryTest extends KernelTestCase
{
    public function testFindOneBailleur(): void
    {
        /** @var BailleurRepository $bailleurRepository */
        $bailleurRepository = static::getContainer()->get(BailleurRepository::class);
        $territory = (new Territory())->setZip('13');

        $bailleur = $bailleurRepository->findOneBailleurBy(
            name: '13 HABITAT',
            territory: $territory,
            bailleurSanitized: true
        );

        $this->assertEquals('13 HABITAT', $bailleur?->getName());
    }

    public function testFindOneBailleurRadie(): void
    {
        /** @var BailleurRepository $bailleurRepository */
        $bailleurRepository = static::getContainer()->get(BailleurRepository::class);
        $territory = (new Territory())->setZip('13');

        $bailleur = $bailleurRepository->findOneBailleurBy(
            name: 'S.A. REGIONALE DE L\'HABITAT',
            territory: $territory,
            bailleurSanitized: true
        );

        $this->assertEquals("[Radié(e)] S.A. REGIONALE DE L'HABITAT", $bailleur?->getName());
    }
}
