<?php

namespace App\Tests\Functional\Service;

use App\Entity\Signalement;
use App\Service\CriticiteCalculatorService;
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
        $signalement = $signalementRepository->find(1);

        $score = new CriticiteCalculatorService($signalement, $this->managerRegistry);
        $oldScore = $score->calculate();
        $newScore = $score->calculateNewCriticite();
        $this->assertIsFloat($oldScore);
        $this->assertIsFloat($newScore);
        $this->assertLessThan($oldScore, $newScore);
        $this->assertLessThan(101, $oldScore);
        $this->assertLessThan(101, $newScore);
    }
}
