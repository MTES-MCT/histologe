<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Affectation;
use App\Entity\Enum\ProcedureType;
use App\Entity\Enum\Qualification;
use App\Entity\Intervention;
use App\Factory\FileFactory;
use App\Factory\InterventionFactory;
use App\Manager\InterventionManager;
use App\Repository\InterventionRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use App\Service\TimezoneProvider;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Workflow\WorkflowInterface;

class InterventionManagerTest extends KernelTestCase
{
    private InterventionRepository $interventionRepository;
    private InterventionFactory $interventionFactory;
    private WorkflowInterface $workflow;
    private SignalementRepository $signalementRepository;
    private SignalementQualificationUpdater $signalementQualificationUpdater;
    private FileFactory $fileFactory;
    private Security $security;
    private EntityManagerInterface $entityManager;
    private HtmlSanitizerInterface $htmlSanitizer;
    private ?InterventionManager $interventionManager = null;
    private TimezoneProvider $timezoneProvider;
    private EventDispatcherInterface $eventDispatcher;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->interventionRepository = static::getContainer()->get(InterventionRepository::class);
        $this->interventionFactory = static::getContainer()->get(InterventionFactory::class);
        $this->workflow = static::getContainer()->get('state_machine.intervention_planning');
        $this->signalementQualificationUpdater = static::getContainer()->get(SignalementQualificationUpdater::class);
        $this->fileFactory = static::getContainer()->get(FileFactory::class);
        $this->security = static::getContainer()->get('security.helper');
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->htmlSanitizer = static::getContainer()->get('html_sanitizer.sanitizer.app.message_sanitizer');
        $this->timezoneProvider = static::getContainer()->get(TimezoneProvider::class);
        $this->eventDispatcher = static::getContainer()->get(EventDispatcherInterface::class);

        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        static::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));

        $this->interventionManager = new InterventionManager(
            $this->interventionRepository,
            $this->interventionFactory,
            $this->workflow,
            $this->signalementQualificationUpdater,
            $this->fileFactory,
            $this->security,
            $this->entityManager,
            $this->htmlSanitizer,
            $this->timezoneProvider,
            $this->eventDispatcher,
        );
    }

    /**
     * @throws \Exception
     */
    public function testCreatePastVisiteFromRequest(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2023-10']);
        /** @var ?Affectation $affectation */
        $affectation = $signalement->getAffectations()->filter(static function (Affectation $affectation) {
            return $affectation->getPartner()->hasCompetence(Qualification::VISITES);
        })->get(0);

        $intervention = (new Intervention())
            ->setSignalement($signalement)
            ->setDetails('Transmission du dossier effectué')
            ->setConcludeProcedure([ProcedureType::MISE_EN_SECURITE_PERIL])
            ->setOccupantPresent(true)
            ->setProprietairePresent(false)
            ->setNotifyUsager(true)
        ;
        $this->entityManager->persist($intervention);

        $this->interventionManager->updateVisiteFromData(
            intervention: $intervention,
            scheduledAt: new \DateTimeImmutable('2023-01-10 00:00'),
            scheduledAtTime: new \DateTimeImmutable('1970-01-01 10:00'),
            partnerChoice: $affectation->getPartner(),
            visiteDone: true,
            fileName: 'blank.pdf',
        );

        $this->assertInstanceOf(Intervention::class, $intervention);
        $this->assertTrue($intervention->getPartner()->hasCompetence(Qualification::VISITES));
        $this->assertTrue($intervention->isOccupantPresent());
        $this->assertFalse($intervention->isProprietairePresent());
        $this->assertEquals($intervention::STATUS_DONE, $intervention->getStatus());
        $this->assertCount(1, $intervention->getFiles());
        $this->assertEquals('Transmission du dossier effectué', $intervention->getDetails());
        $this->assertEquals(new \DateTimeImmutable('2023-01-10 09:00'), $intervention->getScheduledAt());
        $this->assertTrue(
            \in_array(
                ProcedureType::MISE_EN_SECURITE_PERIL,
                $intervention->getConcludeProcedure()
            )
        );

        $this->assertEmailCount(2);
    }

    /**
     * @throws \Exception
     */
    public function testCreateFutureVisiteFromRequest(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2023-10']);
        /** @var ?Affectation $affectation */
        $affectation = $signalement->getAffectations()->filter(static function (Affectation $affectation) {
            return $affectation->getPartner()->hasCompetence(Qualification::VISITES);
        })->get(0);

        $intervention = (new Intervention())->setSignalement($signalement);

        $this->interventionManager->updateVisiteFromData(
            intervention: $intervention,
            scheduledAt: (new \DateTimeImmutable())->modify('+ 1 month'),
            scheduledAtTime: new \DateTimeImmutable('1970-01-01 10:00'),
            partnerChoice: $affectation->getPartner(),
        );

        $this->assertInstanceOf(Intervention::class, $intervention);
        $this->assertEquals(Intervention::STATUS_PLANNED, $intervention->getStatus());
        $this->assertTrue($intervention->getScheduledAt() > new \DateTimeImmutable());
        $this->assertTrue($intervention->getPartner()->hasCompetence(Qualification::VISITES));
        $this->assertEmailCount(1);
    }

    /**
     * @throws \Exception
     */
    public function testCreateFutureVisiteFromRequestOnExternalOperator(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2023-10']);

        $intervention = (new Intervention())
            ->setSignalement($signalement)
            ->setExternalOperator('Lala la')
        ;

        $this->interventionManager->updateVisiteFromData(
            intervention: $intervention,
            scheduledAt: (new \DateTimeImmutable())->modify('+ 1 month'),
            scheduledAtTime: new \DateTimeImmutable('1970-01-01 10:00'),
            partnerChoice: 'extern',
        );

        $this->assertInstanceOf(Intervention::class, $intervention);
        $this->assertEquals(Intervention::STATUS_PLANNED, $intervention->getStatus());
        $this->assertTrue($intervention->getScheduledAt() > new \DateTimeImmutable());
        $this->assertEquals('Lala la', $intervention->getPartner()->getNom());
        $this->assertNull($intervention->getPartner()->getId());
        $this->assertEquals('OPERATEUR_VISITES_ET_TRAVAUX', $intervention->getPartner()->getType()->name);
        $this->assertEquals('Lala la', $intervention->getExternalOperator());
        $this->assertEmailCount(1);
    }

    /**
     * @throws \Exception
     */
    public function testAbortVisiteFromRequest(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2022-6']);
        /** @var Intervention $intervention */
        $intervention = $signalement->getInterventions()->current();

        $intervention->setDetails('Suppression de la visite');

        $this->interventionManager->confirmOrAbortVisiteFromData(
            intervention: $intervention,
            createdByPartner: $intervention->getPartner(),
            visiteDone: false
        );

        $this->assertInstanceOf(Intervention::class, $intervention);
        $this->assertEquals(Intervention::STATUS_NOT_DONE, $intervention->getStatus());
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

        $this->interventionManager->updateVisiteFromData(
            intervention: $intervention,
            scheduledAt: (new \DateTimeImmutable())->modify('+2 months'),
            scheduledAtTime: new \DateTimeImmutable('1970-01-01 20:00:00'),
            partnerChoice: $intervention->getPartner(),
            eventType: 'reschedule'
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

        $intervention
            ->setDetails('Dossier envoyé au service compétent')
            ->setNotifyUsager(true)
        ;

        $this->interventionManager->editConclusionVisiteFromRequest(
            intervention: $intervention,
            createdByPartner: $intervention->getPartner()
        );

        $this->assertEquals(Intervention::STATUS_DONE, $intervention->getStatus());
    }
}
