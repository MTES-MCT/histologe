<?php

namespace App\Tests\Functional\Manager;

use App\Entity\DesordreCategorie;
use App\Manager\DesordreCategorieManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DesordreCategorieManagerTest extends KernelTestCase
{
    protected ManagerRegistry $managerRegistry;
    protected DesordreCategorieManager $desordreCategorieManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        $this->desordreCategorieManager = new DesordreCategorieManager(
            $this->managerRegistry,
            DesordreCategorie::class,
        );
    }

    public function testCreateOrUpdateDesordreCategorie()
    {
        $desordreCategorie = $this->desordreCategorieManager->createOrUpdate(
            'rideaux et moquette'
        );

        $this->assertInstanceOf(DesordreCategorie::class, $desordreCategorie);
        $this->assertEquals($desordreCategorie->getLabel(), 'rideaux et moquette');
    }
}
