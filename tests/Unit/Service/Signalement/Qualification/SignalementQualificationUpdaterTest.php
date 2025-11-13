<?php

namespace App\Tests\Unit\Service\Signalement\Qualification;

use App\Entity\Enum\ProcedureType;
use App\Entity\Signalement;
use App\Factory\SignalementQualificationFactory;
use App\Repository\SignalementRepository;
use App\Service\Signalement\Qualification\QualificationStatusService;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SignalementQualificationUpdaterTest extends KernelTestCase
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

    public function testUpdateFromProcedureInsalubrite(): void
    {
        /** @var MockObject&SignalementQualificationFactory $signalementQualificationFactory */
        $signalementQualificationFactory = $this->createMock(SignalementQualificationFactory::class);
        /** @var MockObject&EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /** @var MockObject&QualificationStatusService $qualificationStatusService */
        $qualificationStatusService = $this->createMock(QualificationStatusService::class);

        /** @var SignalementRepository $signalementRepository */
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
        /** @var MockObject&SignalementQualificationFactory $signalementQualificationFactory */
        $signalementQualificationFactory = $this->createMock(SignalementQualificationFactory::class);
        /** @var MockObject&EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /** @var MockObject&QualificationStatusService $qualificationStatusService */
        $qualificationStatusService = $this->createMock(QualificationStatusService::class);

        /** @var SignalementRepository $signalementRepository */
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
