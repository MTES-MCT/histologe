<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\Signalement;
use App\Service\Signalement\CriticiteCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CriticiteCalculatorTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    protected ManagerRegistry $managerRegistry;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->managerRegistry = static::getContainer()->get(ManagerRegistry::class);
    }

    public function testCalculateBothScoreOnSignalement()
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->find(1);

        $newScore = (new CriticiteCalculator())->calculate($signalement);
        $this->assertIsFloat($newScore);
        $this->assertLessThan(101, $newScore);
        $this->assertEquals(2.87, round($newScore, 2));
    }
}
