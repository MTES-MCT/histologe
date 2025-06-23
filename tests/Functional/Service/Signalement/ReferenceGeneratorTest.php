<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\Territory;
use App\Repository\SignalementRepository;
use App\Service\Signalement\ReferenceGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\TransactionRequiredException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ReferenceGeneratorTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @throws TransactionRequiredException
     * @throws NonUniqueResultException
     */
    public function testGenerateReferenceFromExistingSignalement(): void
    {
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => 13]);

        $todayYear = (new \DateTime())->format('Y');
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
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => 85]);

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
