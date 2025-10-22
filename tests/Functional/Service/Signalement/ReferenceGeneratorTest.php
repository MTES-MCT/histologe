<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\Territory;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Service\Signalement\ReferenceGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\TransactionRequiredException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ReferenceGeneratorTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
    }

    /**
     * @throws TransactionRequiredException
     * @throws NonUniqueResultException
     */
    public function testGenerateReferenceFromExistingSignalement(): void
    {
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => 13]);

        $todayYear = (new \DateTime())->format('Y');
        /** @var SignalementRepository&MockObject $signalementRepository */
        $signalementRepository = $this->createMock(SignalementRepository::class);
        $signalementRepository
            ->expects($this->once())
            ->method('findLastReferenceByTerritory')
            ->with($territory)
            ->willReturn(['reference' => '2022-11']);

        $referenceGenerator = new ReferenceGenerator($signalementRepository);

        $referenceGenerated = $referenceGenerator->generate($territory);
        $this->assertEquals($todayYear.'-12', $referenceGenerated);
    }

    /**
     * @throws TransactionRequiredException
     * @throws NonUniqueResultException
     */
    public function testGenerateReferenceFromNoSignalement(): void
    {
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => 85]);

        /** @var SignalementRepository&MockObject $signalementRepository */
        $signalementRepository = $this->createMock(SignalementRepository::class);
        $signalementRepository
            ->expects($this->once())
            ->method('findLastReferenceByTerritory')
            ->with($territory)
            ->willReturn(null);

        $referenceGenerator = new ReferenceGenerator($signalementRepository);

        $referenceGenerated = $referenceGenerator->generate($territory);
        $year = (new \DateTime())->format('Y');
        $this->assertEquals($year.'-1', $referenceGenerated);
    }
}
