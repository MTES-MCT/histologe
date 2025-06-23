<?php

namespace App\Tests\Unit\Service\Signalement\Qualification;

use App\Entity\Enum\ProcedureType;
use App\Entity\Signalement;
use App\Factory\SignalementQualificationFactory;
use App\Service\Signalement\Qualification\QualificationStatusService;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SignalementQualificationUpdaterTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testUpdateFromProcedureInsalubrite(): void
    {
        $signalementQualificationFactory = $this->createMock(SignalementQualificationFactory::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $qualificationStatusService = $this->createMock(QualificationStatusService::class);

        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-1']);
        $procedureTypes = [ProcedureType::INSALUBRITE];

        $signalementQualificationUpdater = new SignalementQualificationUpdater(
            $signalementQualificationFactory,
            $entityManager,
            $qualificationStatusService,
        );
        $signalementQualificationUpdater->updateQualificationFromVisiteProcedureList($signalement, $procedureTypes);

        $this->assertEquals(\count($signalement->getSignalementQualifications()), 1);
    }

    public function testUpdateFromProcedureAutre(): void
    {
        $signalementQualificationFactory = $this->createMock(SignalementQualificationFactory::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $qualificationStatusService = $this->createMock(QualificationStatusService::class);

        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-1']);
        $procedureTypes = [ProcedureType::AUTRE];

        $signalementQualificationUpdater = new SignalementQualificationUpdater(
            $signalementQualificationFactory,
            $entityManager,
            $qualificationStatusService,
        );
        $signalementQualificationUpdater->updateQualificationFromVisiteProcedureList($signalement, $procedureTypes);

        $this->assertEquals(\count($signalement->getSignalementQualifications()), 0);
    }
}
