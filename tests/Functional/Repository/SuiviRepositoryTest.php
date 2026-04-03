<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SuiviRepositoryTest extends KernelTestCase
{
    private SuiviRepository $suiviRepository;

    private EntityManagerInterface $entityManager;

    public const USER_ADMIN = 'admin-01@signal-logement.fr';

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
        $this->suiviRepository = $this->entityManager->getRepository(Suivi::class);
    }

    public function testFindSignalementsForFirstAskFeedbackRelance(): void
    {
        $result = $this->suiviRepository->findSignalementsForFirstAskFeedbackRelance();
        $this->assertCount(6, $result);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        for ($i = 0; $i < count($result); ++$i) {
            $signalement = $signalementRepository->findOneBy(['id' => $result[$i]]);
            $this->assertContains($signalement->getReference(), ['2023-13', '2023-19', '2023-20', '2023-120', '2024-01', '2024-02']);
        }
    }

    public function testFindSignalementsForSecondAskFeedbackRelance(): void
    {
        $result = $this->suiviRepository->findSignalementsForSecondAskFeedbackRelance();
        $this->assertCount(1, $result);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['id' => $result[0]]);
        $this->assertEquals('2023-14', $signalement->getReference());
    }

    public function testFindSignalementsForThirdAskFeedbackRelance(): void
    {
        $result = $this->suiviRepository->findSignalementsForThirdAskFeedbackRelance();
        $this->assertCount(1, $result);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['id' => $result[0]]);
        $this->assertEquals('2023-15', $signalement->getReference());
    }

    public function testFindSignalementsForLoopAskFeedbackRelance(): void
    {
        $result = $this->suiviRepository->findSignalementsForLoopAskFeedbackRelance();
        $this->assertCount(1, $result);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['id' => $result[0]]);
        $this->assertEquals('2022-8', $signalement->getReference());
    }
}
