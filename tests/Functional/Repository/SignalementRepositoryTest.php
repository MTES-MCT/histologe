<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Signalement;
use App\Entity\Territory;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SignalementRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function testFindByReferenceChunkThrowException(): void
    {
        $this->expectException(NonUniqueResultException::class);

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => '01']);

        $signalement = $signalementRepository->findByReferenceChunk(
            $territory,
            '2022-1'
        );
    }

    /**
     * @throws NonUniqueResultException
     */
    public function testFindByReferenceChunk(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => '01']);

        $signalement = $signalementRepository->findByReferenceChunk(
            $territory,
            '2022-14'
        );

        $this->assertEquals('01', $signalement->getTerritory()->getZip());
    }
}
