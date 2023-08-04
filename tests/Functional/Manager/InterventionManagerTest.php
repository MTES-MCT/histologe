<?php

namespace App\Tests\Functional\Manager;

use App\Dto\Request\Signalement\VisiteRequest;
use App\Entity\Affectation;
use App\Entity\Enum\ProcedureType;
use App\Entity\Enum\Qualification;
use App\Entity\Intervention;
use App\Factory\FileFactory;
use App\Manager\InterventionManager;
use App\Manager\PartnerManager;
use App\Repository\InterventionRepository;
use App\Repository\SignalementRepository;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Workflow\WorkflowInterface;

class InterventionManagerTest extends KernelTestCase
{
    protected ManagerRegistry $managerRegistry;
    private InterventionRepository $interventionRepository;
    private PartnerManager $partnerManager;
    private WorkflowInterface $workflow;
    private SignalementRepository $signalementRepository;
    private SignalementQualificationUpdater $signalementQualificationUpdater;
    private FileFactory $fileFactory;
    private Security $security;

    private ?InterventionManager $interventionManager = null;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->interventionRepository = static::getContainer()->get(InterventionRepository::class);
        $this->partnerManager = static::getContainer()->get(PartnerManager::class);
        $this->workflow = static::getContainer()->get('state_machine.intervention_planning');
        $this->signalementQualificationUpdater = static::getContainer()->get(SignalementQualificationUpdater::class);
        $this->fileFactory = static::getContainer()->get(FileFactory::class);
        $this->security = static::getContainer()->get('security.helper');
        $this->managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);

        $this->interventionManager = new InterventionManager(
            $this->managerRegistry,
            $this->interventionRepository,
            $this->partnerManager,
            $this->workflow,
            $this->signalementQualificationUpdater,
            $this->fileFactory,
            $this->security,
        );
    }

    /**
     * @throws \Exception
     */
    public function testCreatePastVisiteFromRequest(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2023-10']);
        /** @var Affectation $affectation */
        $affectation = $signalement->getAffectations()->filter(function (Affectation $affectation) {
            return $affectation->getPartner()->hasCompetence(Qualification::VISITES);
        })->get(0);

        $visiteRequest = new VisiteRequest(
            date: '2023-01-10',
            time: '10:00',
            idPartner: $affectation?->getPartner()->getId(),
            details: 'Tranmission du dossier effectué',
            concludeProcedure: ['MISE_EN_SECURITE_PERIL'],
            isVisiteDone: true,
            isOccupantPresent: true,
            isProprietairePresent: false,
            isUsagerNotified: true,
            document: 'blank.pdf',
        );

        $intervention = $this->interventionManager->createVisiteFromRequest($signalement, $visiteRequest);

        $this->assertInstanceOf(Intervention::class, $intervention);
        $this->assertTrue($intervention->getPartner()->hasCompetence(Qualification::VISITES));
        $this->assertTrue($intervention->isOccupantPresent());
        $this->assertFalse($intervention->isProprietairePresent());
        $this->assertEquals($intervention::STATUS_DONE, $intervention->getStatus());
        $this->assertCount(1, $intervention->getFiles());
        $this->assertEquals('Tranmission du dossier effectué', $intervention->getDetails());
        $this->assertEquals(new \DateTimeImmutable('2023-01-10 10:00'), $intervention->getScheduledAt());
        $this->assertTrue(
            \in_array(
                ProcedureType::MISE_EN_SECURITE_PERIL,
                $intervention->getConcludeProcedure()
            ));

        $this->assertEmailCount(3);
    }

    /**
     * @throws \Exception
     */
    public function testCreateFutureVisiteFromRequest(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2023-10']);
        /** @var Affectation $affectation */
        $affectation = $signalement->getAffectations()->filter(function (Affectation $affectation) {
            return $affectation->getPartner()->hasCompetence(Qualification::VISITES);
        })->get(0);

        $visiteRequest = new VisiteRequest(
            date: (new \DateTimeImmutable())->modify('+ 1 month')->format('Y-m-d'),
            time: '10:00',
            idPartner: $affectation?->getPartner()->getId(),
        );

        $intervention = $this->interventionManager->createVisiteFromRequest($signalement, $visiteRequest);
        $this->assertInstanceOf(Intervention::class, $intervention);
        $this->assertEquals(Intervention::STATUS_PLANNED, $intervention->getStatus());
        $this->assertTrue($intervention->getScheduledAt() > new \DateTimeImmutable());
        $this->assertTrue($intervention->getPartner()->hasCompetence(Qualification::VISITES));
        $this->assertEmailCount(0);
    }

    /**
     * @throws \Exception
     */
    public function testCancelVisiteFromRequest(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2023-9']);
        /** @var Intervention $intervention */
        $intervention = $signalement->getInterventions()->current();

        $visiteRequest = new VisiteRequest(
            idIntervention: $intervention->getId(),
            details: 'Suppression de la visite'
        );

        $intervention = $this->interventionManager->cancelVisiteFromRequest($visiteRequest);
        $this->assertInstanceOf(Intervention::class, $intervention);
        $this->assertEquals(Intervention::STATUS_CANCELED, $intervention->getStatus());
        $this->assertEquals('Suppression de la visite', $intervention->getDetails());
    }

    /**
     * @throws \Exception
     */
    public function testRescheduleVisiteFromRequest(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2023-9']);
        /** @var Intervention $intervention */
        $intervention = $signalement->getInterventions()->current();

        $visiteRequest = new VisiteRequest(
            idIntervention: $intervention->getId(),
            date: (new \DateTimeImmutable())->modify('+2 months')->format('Y-m-d'),
            time: '20:00',
            idPartner: $intervention->getPartner()->getId(),
            details: '',
        );

        $intervention = $this->interventionManager->rescheduleVisiteFromRequest(
            signalement: $signalement,
            visiteRequest: $visiteRequest
        );
        $this->assertInstanceOf(Intervention::class, $intervention);
        $this->assertEquals(Intervention::STATUS_PLANNED, $intervention->getStatus());
        $this->assertTrue($intervention->getScheduledAt() > new \DateTimeImmutable());
    }

    public function testEditVisiteFromRequest(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2023-10']);
        /** @var Intervention $intervention */
        $intervention = $signalement->getInterventions()->current();
        $visiteRequest = new VisiteRequest(
            idIntervention: $intervention->getId(),
            details: 'Dossier envoyé au service compétent',
            isUsagerNotified: true,
        );

        $intervention = $this->interventionManager->editVisiteFromRequest(
            visiteRequest: $visiteRequest
        );

        $this->assertEquals(Intervention::STATUS_DONE, $intervention->getStatus());
    }
}
