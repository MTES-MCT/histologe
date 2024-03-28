<?php

namespace App\Tests\Functional\Repository;

use App\Repository\BailleurRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BailleurRepositoryTest extends KernelTestCase
{
    public function testFindOneBailleurRadie(): void
    {
        /** @var BailleurRepository $bailleurRepository */
        $bailleurRepository = static::getContainer()->get(BailleurRepository::class);

        $bailleur = $bailleurRepository->findOneBailleurBy(
            name: 'S.A. REGIONALE DE L\'HABITAT',
            zip: '13',
            bailleurSanitized: true
        );

        $this->assertEquals("[Radié(e)] S.A. REGIONALE DE L'HABITAT", $bailleur?->getName());
    }
}
