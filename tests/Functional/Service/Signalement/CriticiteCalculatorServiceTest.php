<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\Critere;
use App\Entity\Signalement;
use App\Service\Signalement\CriticiteCalculatorService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CriticiteCalculatorServiceTest extends KernelTestCase
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
        $critereRepository = $this->entityManager->getRepository(Critere::class);
        $signalement = $signalementRepository->find(1);

        $score = new CriticiteCalculatorService($signalement, $critereRepository);
        $oldScore = $score->calculate();
        $newScore = $score->calculateNewCriticite();
        $this->assertIsFloat($oldScore);
        $this->assertIsFloat($newScore);
        $this->assertLessThan($oldScore, $newScore);
        $this->assertLessThan(101, $oldScore);
        $this->assertLessThan(101, $newScore);
        $this->assertEquals(36.53, round($oldScore, 2));
        $this->assertEquals(2.87, round($newScore, 2));
    }
}
