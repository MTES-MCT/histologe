<?php

namespace App\Tests\Functional\Repository\Query\Statistics;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Signalement;
use App\Repository\Behaviour\AffectationUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AffectationUpdaterTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private AffectationUpdater $affectationUpdater;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
        $this->affectationUpdater = static::getContainer()->get(AffectationUpdater::class);
    }

    public function testUpdateStatusBySignalement(): void
    {
        /** @var AffectationRepository $affectationRepository */
        $affectationRepository = $this->entityManager->getRepository(Affectation::class);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000004']);
        $this->affectationUpdater->updateStatusBySignalement(AffectationStatus::WAIT, $signalement);
        $affectations = $affectationRepository->findBy(['signalement' => $signalement, 'statut' => AffectationStatus::WAIT]);
        $this->assertCount(2, $affectations);
    }
}
