<?php

namespace App\Tests\Unit\Service\Esabora;

use App\Entity\Affectation;
use App\Entity\Arrete;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\InterventionType;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Intervention;
use App\Entity\Suivi;
use App\Entity\User;
use App\Factory\FileFactory;
use App\Factory\InterventionFactory;
use App\Manager\AffectationManager;
use App\Manager\ArreteManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Manager\UserSignalementSubscriptionManager;
use App\Repository\ArreteRepository;
use App\Repository\InterventionRepository;
use App\Repository\SuiviRepository;
use App\Service\Files\ImageManipulationHandler;
use App\Service\Files\ZipHelper;
use App\Service\Interconnection\Esabora\Enum\EsaboraStatus;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\Response\DossierResponseInterface;
use App\Service\Security\FileScanner;
use App\Service\Signalement\Suivi\SuiviDescriptionHelper;
use App\Service\TimezoneProvider;
use App\Service\UploadHandlerService;
use App\Tests\FixturesHelper;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\WorkflowInterface;

class EsaboraManagerTest extends KernelTestCase
{
    use FixturesHelper;
    protected const string CREATE_ACTION = 'create';
    protected const string UPDATE_ACTION = 'update';

    protected Stub&AffectationManager $affectationManager;
    protected Stub&SuiviManager $suiviManager;
    protected Stub&SuiviRepository $suiviRepository;
    protected Stub&InterventionRepository $interventionRepository;
    protected Stub&ArreteRepository $arreteRepository;
    protected Stub&InterventionFactory $interventionFactory;
    protected Stub&ArreteManager $arreteManager;
    protected EventDispatcher $eventDispatcher;
    protected Stub&UserManager $userManager;
    private Stub&LoggerInterface $logger;
    private Stub&EntityManager $entityManager;
    private Stub&ZipHelper $zipHelper;
    private Stub&FileScanner $fileScanner;
    private Stub&UploadHandlerService $uploadHandler;
    private Stub&ImageManipulationHandler $imageManipulationHandler;
    private Stub&FileFactory $fileFactory;
    private WorkflowInterface $workflow;
    private Stub&UserSignalementSubscriptionManager $userSignalementSubscriptionManager;
    private SuiviDescriptionHelper $suiviDescriptionHelper;

    protected function setUp(): void
    {
        $this->affectationManager = $this->createStub(AffectationManager::class);
        $this->suiviManager = $this->createStub(SuiviManager::class);
        $this->suiviRepository = $this->createStub(SuiviRepository::class);
        $this->interventionRepository = $this->createStub(InterventionRepository::class);
        $this->arreteRepository = $this->createStub(ArreteRepository::class);
        $this->interventionFactory = $this->createStub(InterventionFactory::class);
        $this->arreteManager = $this->createStub(ArreteManager::class);
        $this->userManager = $this->createStub(UserManager::class);
        $this->eventDispatcher = new EventDispatcher();
        $this->logger = $this->createStub(LoggerInterface::class);
        $this->entityManager = $this->createStub(EntityManager::class);
        $this->zipHelper = $this->createStub(ZipHelper::class);
        $this->fileScanner = $this->createStub(FileScanner::class);
        $this->uploadHandler = $this->createStub(UploadHandlerService::class);
        $this->imageManipulationHandler = $this->createStub(ImageManipulationHandler::class);
        $this->fileFactory = $this->createStub(FileFactory::class);
        $this->workflow = static::getContainer()->get('state_machine.intervention_planning');
        $this->userSignalementSubscriptionManager = $this->createStub(UserSignalementSubscriptionManager::class);
        $this->suiviDescriptionHelper = static::getContainer()->get(SuiviDescriptionHelper::class);
    }

    /**
     * @throws \Exception
     */
    public function testCreateVisite(): void
    {
        $dossierVisiteCollection = $this->getDossierVisiteSISHCollectionResponse()->getCollection();
        $dossierVisite = $dossierVisiteCollection[0];
        $esaboraManager = $this->provideEsaboraManagerForIntervention(self::CREATE_ACTION, InterventionType::VISITE);
        $esaboraManager->createOrUpdateVisite($this->getAffectation(PartnerType::ARS), $dossierVisite);
    }

    /**
     * @throws \Exception
     */
    public function testFailureCreateVisite(): void
    {
        $dossierVisiteCollection = $this->getDossierVisiteSISHCollectionWithDossierResponse()->getCollection();
        $dossierVisite = $dossierVisiteCollection[0];
        $interventionRepository = $this->createMock(InterventionRepository::class);
        $this->interventionRepository = $interventionRepository;
        $interventionRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $interventionFactory = $this->createMock(InterventionFactory::class);
        $this->interventionFactory = $interventionFactory;
        $interventionFactory
            ->expects($this->never())
            ->method('createInstanceFrom');

        $logger = $this->createMock(LoggerInterface::class);
        $this->logger = $logger;
        $logger
            ->expects($this->once())
            ->method('error');

        $esaboraManager = new EsaboraManager(
            $this->affectationManager,
            $this->suiviManager,
            $this->suiviRepository,
            $this->interventionRepository,
            $this->arreteRepository,
            $this->interventionFactory,
            $this->arreteManager,
            $this->eventDispatcher, // @phpstan-ignore-line
            $this->userManager,
            $this->logger,
            $this->entityManager,
            $this->zipHelper,
            $this->fileScanner,
            $this->uploadHandler,
            $this->imageManipulationHandler,
            $this->fileFactory,
            $this->workflow,
            $this->userSignalementSubscriptionManager,
            true,
            $this->suiviDescriptionHelper,
        );
        $esaboraManager->createOrUpdateVisite($this->getAffectation(PartnerType::ARS), $dossierVisite);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateVisite(): void
    {
        $dossierVisiteCollection = $this->getDossierVisiteSISHCollectionResponse()->getCollection();
        $dossierVisite = $dossierVisiteCollection[0];
        $esaboraManager = $this->provideEsaboraManagerForIntervention(self::UPDATE_ACTION, InterventionType::VISITE);
        $esaboraManager->createOrUpdateVisite($this->getAffectation(PartnerType::ARS), $dossierVisite);
    }

    /**
     * @throws \Exception
     */
    public function testCreateArrete(): void
    {
        $dossierArrete = $this->getDossierArreteSISHCollectionResponse()->getCollection()[0];
        $affectation = $this->getAffectation(PartnerType::ARS);

        $arreteRepository = $this->createMock(ArreteRepository::class);
        $this->arreteRepository = $arreteRepository;
        $arreteRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
        $arreteManager = $this->createMock(ArreteManager::class);
        $this->arreteManager = $arreteManager;
        $arreteManager
            ->expects($this->once())
            ->method('createArreteFromDossierArreteSISH')
            ->with($dossierArrete, $affectation);
        $arreteManager
            ->expects($this->never())
            ->method('updateFromDossierArreteSISH');
        $entityManager = $this->createMock(EntityManager::class);
        $this->entityManager = $entityManager;
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $this->createEsaboraManager()->createOrUpdateArrete($affectation, $dossierArrete);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateArrete(): void
    {
        $dossierArrete = $this->getDossierArreteSISHCollectionResponse()->getCollection()[0];
        $affectation = $this->getAffectation(PartnerType::ARS);
        $arrete = new Arrete();

        $arreteRepository = $this->createMock(ArreteRepository::class);
        $this->arreteRepository = $arreteRepository;
        $arreteRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($arrete);
        $arreteManager = $this->createMock(ArreteManager::class);
        $this->arreteManager = $arreteManager;
        $arreteManager
            ->expects($this->once())
            ->method('updateFromDossierArreteSISH')
            ->with($arrete, $dossierArrete, $affectation);
        $arreteManager
            ->expects($this->never())
            ->method('createArreteFromDossierArreteSISH');
        $entityManager = $this->createMock(EntityManager::class);
        $this->entityManager = $entityManager;
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $this->createEsaboraManager()->createOrUpdateArrete($affectation, $dossierArrete);
    }

    public function testCreateArreteLogsErrorWhenDateIsInvalid(): void
    {
        $dossierArrete = $this->getDossierArreteSISHCollectionResponse()->getCollection()[0];
        $affectation = $this->getAffectation(PartnerType::ARS);

        $arreteRepository = $this->createMock(ArreteRepository::class);
        $this->arreteRepository = $arreteRepository;
        $arreteRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
        $arreteManager = $this->createMock(ArreteManager::class);
        $this->arreteManager = $arreteManager;
        $arreteManager
            ->expects($this->once())
            ->method('createArreteFromDossierArreteSISH')
            ->willThrowException(new \InvalidArgumentException('Date Esabora invalide'));
        $entityManager = $this->createMock(EntityManager::class);
        $this->entityManager = $entityManager;
        $entityManager
            ->expects($this->never())
            ->method('flush');
        $logger = $this->createMock(LoggerInterface::class);
        $this->logger = $logger;
        $logger
            ->expects($this->once())
            ->method('error');

        $this->createEsaboraManager()->createOrUpdateArrete($affectation, $dossierArrete);
    }

    private function createEsaboraManager(): EsaboraManager
    {
        return new EsaboraManager(
            $this->affectationManager,
            $this->suiviManager,
            $this->suiviRepository,
            $this->interventionRepository,
            $this->arreteRepository,
            $this->interventionFactory,
            $this->arreteManager,
            $this->eventDispatcher, // @phpstan-ignore-line
            $this->userManager,
            $this->logger,
            $this->entityManager,
            $this->zipHelper,
            $this->fileScanner,
            $this->uploadHandler,
            $this->imageManipulationHandler,
            $this->fileFactory,
            $this->workflow,
            $this->userSignalementSubscriptionManager,
            true,
            $this->suiviDescriptionHelper,
        );
    }

    private function provideEsaboraManagerForIntervention(string $action, InterventionType $interventionType): EsaboraManager
    {
        $interventionRepository = $this->createMock(InterventionRepository::class);
        $this->interventionRepository = $interventionRepository;
        $interventionRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(self::CREATE_ACTION === $action
                ? null
                : $this->getIntervention(
                    $interventionType,
                    new \DateTimeImmutable('2024-01-20'),
                    Intervention::STATUS_DONE)
            );

        if (self::CREATE_ACTION === $action) {
            $interventionFactory = $this->createMock(InterventionFactory::class);
            $this->interventionFactory = $interventionFactory;
            $interventionFactory
                ->expects($this->once())
                ->method('createInstanceFrom');
        }

        $userManager = $this->createMock(UserManager::class);
        $this->userManager = $userManager;
        $userManager
            ->expects($this->once())
            ->method('getSystemUser')
            ->willReturn($this->getUser([User::ROLE_ADMIN]));

        return new EsaboraManager(
            $this->affectationManager,
            $this->suiviManager,
            $this->suiviRepository,
            $this->interventionRepository,
            $this->arreteRepository,
            $this->interventionFactory,
            $this->arreteManager,
            $this->eventDispatcher, // @phpstan-ignore-line
            $this->userManager,
            $this->logger,
            $this->entityManager,
            $this->zipHelper,
            $this->fileScanner,
            $this->uploadHandler,
            $this->imageManipulationHandler,
            $this->fileFactory,
            $this->workflow,
            $this->userSignalementSubscriptionManager,
            true,
            $this->suiviDescriptionHelper,
        );
    }

    /**
     * @throws \ReflectionException
     */
    public function testUpdateFromDossierVisiteReturnsTrueWhenDataChanges(): void
    {
        $intervention = new Intervention();
        $intervention->setScheduledAt(new \DateTimeImmutable('2024-01-01 10:00:00'));
        $intervention->setDoneBy('ARS');
        $intervention->setExternalOperator('ARS');
        $intervention->setPartner(null);

        $territory = $this->getTerritory();
        $territory->setTimezone(TimezoneProvider::TIMEZONE_EUROPE_PARIS);
        $signalement = $this->getSignalement();
        $intervention->setSignalement($signalement);

        $signalement->getAddress()->setTerritory($territory);

        $affectation = new Affectation();
        $affectation->setSignalement($signalement);

        $dossierVisiteCollection = $this->getDossierVisiteSISHCollectionResponse()->getCollection();
        $dossierVisite = $dossierVisiteCollection[0];

        $esaboraManager = new EsaboraManager(
            $this->affectationManager,
            $this->suiviManager,
            $this->suiviRepository,
            $this->interventionRepository,
            $this->arreteRepository,
            $this->interventionFactory,
            $this->arreteManager,
            $this->eventDispatcher, // @phpstan-ignore-line
            $this->userManager,
            $this->logger,
            $this->entityManager,
            $this->zipHelper,
            $this->fileScanner,
            $this->uploadHandler,
            $this->imageManipulationHandler,
            $this->fileFactory,
            $this->workflow,
            $this->userSignalementSubscriptionManager,
            true,
            $this->suiviDescriptionHelper,
        );

        $reflector = new \ReflectionClass($esaboraManager);
        $method = $reflector->getMethod('updateFromDossierVisite');
        $result = $method->invoke($esaboraManager, $intervention, $dossierVisite, $affectation);

        $this->assertTrue($result);
        $this->assertEquals(new \DateTimeImmutable('2023-05-03 08:16:00'), $intervention->getScheduledAt());
        $this->assertEquals('SH', $intervention->getDoneBy());
        $this->assertEquals('SH', $intervention->getExternalOperator());
    }

    /**
     * @throws \ReflectionException
     */
    public function testUpdateFromDossierVisiteReturnsFalseWhenNoChanges(): void
    {
        $intervention = new Intervention();
        $intervention->setScheduledAt(new \DateTimeImmutable('2023-05-03 08:16:00'));
        $intervention->setDoneBy('SH');
        $intervention->setExternalOperator('SH');
        $intervention->setPartner(null);

        $territory = $this->getTerritory();
        $territory->setTimezone(TimezoneProvider::TIMEZONE_EUROPE_PARIS);
        $signalement = $this->getSignalement();
        $intervention->setSignalement($signalement);

        $signalement->getAddress()->setTerritory($territory);

        $affectation = new Affectation();
        $affectation->setSignalement($signalement);

        $dossierVisiteCollection = $this->getDossierVisiteSISHCollectionResponse()->getCollection();
        $dossierVisite = $dossierVisiteCollection[0];

        $esaboraManager = new EsaboraManager(
            $this->affectationManager,
            $this->suiviManager,
            $this->suiviRepository,
            $this->interventionRepository,
            $this->arreteRepository,
            $this->interventionFactory,
            $this->arreteManager,
            $this->eventDispatcher, // @phpstan-ignore-line
            $this->userManager,
            $this->logger,
            $this->entityManager,
            $this->zipHelper,
            $this->fileScanner,
            $this->uploadHandler,
            $this->imageManipulationHandler,
            $this->fileFactory,
            $this->workflow,
            $this->userSignalementSubscriptionManager,
            true,
            $this->suiviDescriptionHelper,
        );

        $reflector = new \ReflectionClass($esaboraManager);
        $method = $reflector->getMethod('updateFromDossierVisite');
        $result = $method->invoke($esaboraManager, $intervention, $dossierVisite, $affectation);

        $this->assertFalse($result);
        $this->assertEquals(new \DateTimeImmutable('2023-05-03 08:16:00'), $intervention->getScheduledAt());
        $this->assertEquals('SH', $intervention->getDoneBy());
        $this->assertEquals('SH', $intervention->getExternalOperator());
    }

    /**
     * @throws \DateInvalidTimeZoneException
     * @throws \DateMalformedStringException
     */
    public function testUpdateStatusAndBuildDescriptionWithResynchronisation(): void
    {
        $signalement = $this->getSignalement();
        $partner = $this->getPartner();
        $partner->setNom('Partenaire 01-01');
        $affectation = $this->getAffectation(PartnerType::ARS);
        $affectation->setPartner($partner);
        $affectation->setSignalement($signalement);
        $affectation->setStatut(AffectationStatus::WAIT);
        $user = $this->getUser([User::ROLE_ADMIN]);

        $dossierResponse = $this->createStub(DossierResponseInterface::class);
        $dossierResponse->method('getSasEtat')->willReturn(EsaboraStatus::ESABORA_ACCEPTED->value);
        $dossierResponse->method('getEtat')->willReturn(EsaboraStatus::ESABORA_IN_PROGRESS->value);
        $dossierResponse->method('getNameSI')->willReturn('SISH');
        $dossierResponse->method('getDossNum')->willReturn('2023/SISH/0010');

        $suiviRepository = $this->createMock(SuiviRepository::class);

        $suiviRepository
            ->expects($this->once())
            ->method('findSuiviByDescription')
            ->with(
                $signalement,
                'Signalement <b>accepté par Partenaire 01-01 via SISH',
                SuiviCategory::SIGNALEMENT_STATUS_IS_SYNCHRO
            )
            ->willReturn([new Suivi()]);

        $esaboraManager = new EsaboraManager(
            $this->affectationManager,
            $this->suiviManager,
            $suiviRepository,
            $this->interventionRepository,
            $this->arreteRepository,
            $this->interventionFactory,
            $this->arreteManager,
            $this->eventDispatcher,
            $this->userManager,
            $this->logger,
            $this->entityManager,
            $this->zipHelper,
            $this->fileScanner,
            $this->uploadHandler,
            $this->imageManipulationHandler,
            $this->fileFactory,
            $this->workflow,
            $this->userSignalementSubscriptionManager,
            true,
            $this->suiviDescriptionHelper,
        );

        $description = $esaboraManager->updateStatusAndBuildDescription($affectation, $user, $dossierResponse);
        $this->assertStringContainsString('resynchronisé avec Partenaire 01-01 via SISH (Dossier 2023/SISH/0010)', $description);
    }
}
