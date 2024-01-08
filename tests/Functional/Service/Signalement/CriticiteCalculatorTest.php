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

    public function testCalculateScoreOnOldSignalement()
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->find(1);

        $newScore = (new CriticiteCalculator())->calculate($signalement);
        $this->assertIsFloat($newScore);
        $this->assertLessThan(101, $newScore);
        $this->assertEquals(3.16, round($newScore, 2));
    }

    public function testCalculateFromNewFormulaire()
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-27']);

        $newScore = (new CriticiteCalculator())->calculateFromNewFormulaire($signalement);
        $this->assertIsFloat($newScore);
        $this->assertLessThan(101, $newScore);
        $this->assertEquals(34.04, round($newScore, 2));
        $this->assertNotNull($signalement->getScoreLogement());
        $this->assertNotNull($signalement->getScoreBatiment());
        $this->assertIsFloat($signalement->getScoreLogement());
        $this->assertIsFloat($signalement->getScoreBatiment());
        $this->assertEquals(14.26, round($signalement->getScoreLogement(), 2));
        $this->assertEquals(47.62, round($signalement->getScoreBatiment(), 2));
    }
}
