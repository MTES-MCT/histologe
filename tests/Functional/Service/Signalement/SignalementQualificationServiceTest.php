<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\Enum\QualificationStatus;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Service\Signalement\SignalementQualificationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SignalementQualificationServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    protected ManagerRegistry $managerRegistry;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->managerRegistry = static::getContainer()->get(ManagerRegistry::class);
    }

    public function testUpdateNonDecenceEnergetiqueStatus()
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()[0];

        $qualificationService = new SignalementQualificationService();
        $signalementQualification->setStatus($qualificationService->getNDEStatus($signalementQualification));

        $status = $qualificationService->getNDEStatus($signalementQualification);

        $this->assertEquals(QualificationStatus::NDE_AVEREE, $status);
    }
}
