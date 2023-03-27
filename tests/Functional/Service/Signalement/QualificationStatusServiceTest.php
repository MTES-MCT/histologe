<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Dto\Request\Signalement\QualificationNDERequest;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Service\Signalement\QualificationStatusService;
use DateTimeImmutable;
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

    public function testUpdateNdeStatusNoDateDernierBail()
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()[0];
        $signalementQualification->setDernierBailAt(null);

        $qualificationService = new QualificationStatusService();
        $signalementQualification->setStatus($qualificationService->getNDEStatus($signalementQualification));

        $status = $qualificationService->getNDEStatus($signalementQualification);

        $this->assertEquals(QualificationStatus::NDE_CHECK, $status);
    }

    public function testUpdateNdeStatusBailBefore2023()
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()[0];
        $signalementQualification->setDernierBailAt(new DateTimeImmutable('2022-01-01'));

        $qualificationService = new QualificationStatusService();
        $signalementQualification->setStatus($qualificationService->getNDEStatus($signalementQualification));

        $status = $qualificationService->getNDEStatus($signalementQualification);

        $this->assertEquals(QualificationStatus::ARCHIVED, $status);
    }

    public function testUpdateNdeStatusUnknownDpe()
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()[0];

        $qualificationNDERequest = new QualificationNDERequest(
            dateEntree: '2023-02-01',
            dateDernierBail: '2023-02-01',
            dateDernierDPE: null,
            superficie: null,
            consommationEnergie: null,
            dpe: null
        );
        $signalementQualification->setDernierBailAt(new DateTimeImmutable($qualificationNDERequest->getDateDernierBail()));
        $signalementQualification->setDetails($qualificationNDERequest->getDetails());
        $signalement->setSuperficie($qualificationNDERequest->getSuperficie());

        $qualificationService = new QualificationStatusService();
        $signalementQualification->setStatus($qualificationService->getNDEStatus($signalementQualification));

        $status = $qualificationService->getNDEStatus($signalementQualification);

        $this->assertEquals(QualificationStatus::NDE_CHECK, $status);
    }

    public function testUpdateNdeStatusNoDpe()
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()[0];

        $qualificationNDERequest = new QualificationNDERequest(
            dateEntree: '2023-02-01',
            dateDernierBail: '2023-02-01',
            dateDernierDPE: null,
            superficie: null,
            consommationEnergie: null,
            dpe: false
        );
        $signalementQualification->setDernierBailAt(new DateTimeImmutable($qualificationNDERequest->getDateDernierBail()));
        $signalementQualification->setDetails($qualificationNDERequest->getDetails());
        $signalement->setSuperficie($qualificationNDERequest->getSuperficie());

        $qualificationService = new QualificationStatusService();
        $signalementQualification->setStatus($qualificationService->getNDEStatus($signalementQualification));

        $status = $qualificationService->getNDEStatus($signalementQualification);

        $this->assertEquals(QualificationStatus::NDE_AVEREE, $status);
    }

    public function testUpdateNdeStatusDpeOlder2023Averee()
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()[0];

        $qualificationNDERequest = new QualificationNDERequest(
            dateEntree: '2023-02-01',
            dateDernierBail: '2023-02-01',
            dateDernierDPE: '2022-02-01',
            superficie: 30,
            consommationEnergie: 30000,
            dpe: true
        );
        $signalementQualification->setDernierBailAt(new DateTimeImmutable($qualificationNDERequest->getDateDernierBail()));
        $signalementQualification->setDetails($qualificationNDERequest->getDetails());
        $signalement->setSuperficie($qualificationNDERequest->getSuperficie());

        $qualificationService = new QualificationStatusService();
        $signalementQualification->setStatus($qualificationService->getNDEStatus($signalementQualification));

        $status = $qualificationService->getNDEStatus($signalementQualification);

        $this->assertEquals(QualificationStatus::NDE_AVEREE, $status);
    }

    public function testUpdateNdeStatusDpeOlder2023Ok()
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()[0];

        $qualificationNDERequest = new QualificationNDERequest(
            dateEntree: '2023-02-01',
            dateDernierBail: '2023-02-01',
            dateDernierDPE: '2022-02-01',
            superficie: 30,
            consommationEnergie: 10000,
            dpe: true
        );
        $signalementQualification->setDernierBailAt(new DateTimeImmutable($qualificationNDERequest->getDateDernierBail()));
        $signalementQualification->setDetails($qualificationNDERequest->getDetails());
        $signalement->setSuperficie($qualificationNDERequest->getSuperficie());

        $qualificationService = new QualificationStatusService();
        $signalementQualification->setStatus($qualificationService->getNDEStatus($signalementQualification));

        $status = $qualificationService->getNDEStatus($signalementQualification);

        $this->assertEquals(QualificationStatus::NDE_OK, $status);
    }

    public function testUpdateNdeStatusDpeAfter2023Averee()
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()[0];

        $qualificationNDERequest = new QualificationNDERequest(
            dateEntree: '2023-02-01',
            dateDernierBail: '2023-02-01',
            dateDernierDPE: '2023-02-01',
            superficie: 100,
            consommationEnergie: 580,
            dpe: true
        );
        $signalementQualification->setDernierBailAt(new DateTimeImmutable($qualificationNDERequest->getDateDernierBail()));
        $signalementQualification->setDetails($qualificationNDERequest->getDetails());
        $signalement->setSuperficie($qualificationNDERequest->getSuperficie());

        $qualificationService = new QualificationStatusService();
        $signalementQualification->setStatus($qualificationService->getNDEStatus($signalementQualification));

        $status = $qualificationService->getNDEStatus($signalementQualification);

        $this->assertEquals(QualificationStatus::NDE_AVEREE, $status);
    }

    public function testUpdateNdeStatusDpeAfter2023Ok()
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()[0];

        $qualificationNDERequest = new QualificationNDERequest(
            dateEntree: '2023-02-01',
            dateDernierBail: '2023-02-01',
            dateDernierDPE: '2023-02-01',
            superficie: 100,
            consommationEnergie: 320,
            dpe: true
        );
        $signalementQualification->setDernierBailAt(new DateTimeImmutable($qualificationNDERequest->getDateDernierBail()));
        $signalementQualification->setDetails($qualificationNDERequest->getDetails());
        $signalement->setSuperficie($qualificationNDERequest->getSuperficie());

        $qualificationService = new QualificationStatusService();
        $signalementQualification->setStatus($qualificationService->getNDEStatus($signalementQualification));

        $status = $qualificationService->getNDEStatus($signalementQualification);

        $this->assertEquals(QualificationStatus::NDE_OK, $status);
    }
}
