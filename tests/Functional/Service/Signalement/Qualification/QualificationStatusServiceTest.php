<?php

namespace App\Tests\Functional\Service\Signalement\Qualification;

use App\Dto\Request\Signalement\QualificationNDERequest;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Repository\SignalementRepository;
use App\Service\Signalement\Qualification\QualificationStatusService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class QualificationStatusServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    protected ManagerRegistry $managerRegistry;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->managerRegistry = static::getContainer()->get(ManagerRegistry::class);
    }

    /**
     * @dataProvider provideNDERequestAndStatus
     */
    public function testUpdateNdeStatus(QualificationNDERequest $qualificationNDERequest, QualificationStatus $qualificationStatus): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()[0];

        $signalementQualification->setDetails($qualificationNDERequest->getDetails());
        $signalement->setSuperficie($qualificationNDERequest->getSuperficie());

        $qualificationStatusService = new QualificationStatusService();
        $signalementQualification->setStatus($qualificationStatusService->getNDEStatus($signalementQualification));

        $status = $qualificationStatusService->getNDEStatus($signalementQualification);

        $this->assertEquals($qualificationStatus, $status);
    }

    public function provideNDERequestAndStatus(): \Generator
    {
        $qualificationNDERequest = new QualificationNDERequest(
            dateEntree: null,
            dateDernierDPE: null,
            superficie: null,
            consommationEnergie: null,
            dpe: null
        );
        yield 'No date bail Status Check' => [$qualificationNDERequest, QualificationStatus::NDE_CHECK];
        $qualificationNDERequest = new QualificationNDERequest(
            dateEntree: '2023-01-01',
            dateDernierDPE: null,
            superficie: null,
            consommationEnergie: null,
            dpe: null
        );
        yield 'Unknown DPE Status NDE Check' => [$qualificationNDERequest, QualificationStatus::NDE_CHECK];
        $qualificationNDERequest = new QualificationNDERequest(
            dateEntree: '2023-02-01',
            dateDernierDPE: null,
            superficie: null,
            consommationEnergie: null,
            dpe: false
        );
        yield 'No DPE Status NDE Avérée' => [$qualificationNDERequest, QualificationStatus::NDE_AVEREE];
        $qualificationNDERequest = new QualificationNDERequest(
            dateEntree: '2023-02-01',
            dateDernierDPE: '2022-02-01',
            superficie: 30,
            consommationEnergie: 30000,
            dpe: true
        );
        yield 'DPE Before 2023 Status NDE Avérée' => [$qualificationNDERequest, QualificationStatus::NDE_AVEREE];
        $qualificationNDERequest = new QualificationNDERequest(
            dateEntree: '2023-02-01',
            dateDernierDPE: '2022-02-01',
            superficie: 30,
            consommationEnergie: 10000,
            dpe: true
        );
        yield 'DPE Before 2023 Status NDE OK' => [$qualificationNDERequest, QualificationStatus::NDE_OK];
        $qualificationNDERequest = new QualificationNDERequest(
            dateEntree: '2023-02-01',
            dateDernierDPE: '2023-02-01',
            superficie: 100,
            consommationEnergie: 580,
            dpe: true
        );
        yield 'DPE after 2023 Status NDE Avérée' => [$qualificationNDERequest, QualificationStatus::NDE_AVEREE];
        $qualificationNDERequest = new QualificationNDERequest(
            dateEntree: '2023-02-01',
            dateDernierDPE: '2023-02-01',
            superficie: 100,
            consommationEnergie: 320,
            dpe: true
        );
        yield 'DPE after 2023 Status NDE OK' => [$qualificationNDERequest, QualificationStatus::NDE_OK];
    }
}
