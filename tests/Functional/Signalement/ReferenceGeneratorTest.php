<?php

namespace App\Tests\Functional\Signalement;

use App\Entity\Signalement;
use App\Entity\Territory;
use App\Repository\SignalementRepository;
use App\Service\Signalement\ReferenceGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ReferenceGeneratorTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testGenerateReferenceFromExistingSignalement()
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => 13]);

        $referenceGenerator = new ReferenceGenerator($signalementRepository);

        $referenceGenerated = $referenceGenerator->generate($territory);

        $this->assertEquals('2022-4', $referenceGenerated);
    }

    public function testGenerateReferenceFromNoSignalement()
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => 44]);

        $referenceGenerator = new ReferenceGenerator($signalementRepository);

        $referenceGenerated = $referenceGenerator->generate($territory);
        $year = (new \DateTime())->format('Y');
        $this->assertEquals($year.'-1', $referenceGenerated);
    }
}
