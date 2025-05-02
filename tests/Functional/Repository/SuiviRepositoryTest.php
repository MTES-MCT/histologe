<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Repository\SuiviRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SuiviRepositoryTest extends KernelTestCase
{
    private SuiviRepository $suiviRepository;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->suiviRepository = $this->entityManager->getRepository(Suivi::class);
    }

    public function testFindSignalementsForThirdRelance(): void
    {
        $result = $this->suiviRepository->findSignalementsForThirdRelance();
        $this->assertCount(1, $result);
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['id' => $result[0]]);
        $this->assertEquals('2023-15', $signalement->getReference());
    }

    public function testCountSignalementNoSuiviAfter3Relances(): void
    {
        $result = $this->suiviRepository->countSignalementNoSuiviAfter3Relances([]);
        $this->assertEquals(0, $result);
    }
}
