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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\WorkflowInterface;

class EsaboraManagerTest extends KernelTestCase
{
    use FixturesHelper;
    protected const string CREATE_ACTION = 'create';
    protected const string UPDATE_ACTION = 'update';

    protected MockObject&AffectationManager $affectationManager;
    protected MockObject&SuiviManager $suiviManager;
    protected MockObject&SuiviRepository $suiviRepository;
    protected MockObject&InterventionRepository $interventionRepository;
    protected MockObject&ArreteRepository $arreteRepository;
    protected MockObject&InterventionFactory $interventionFactory;
    protected MockObject&ArreteManager $arreteManager;
    protected EventDispatcher $eventDispatcher;
    protected MockObject&UserManager $userManager;
    private MockObject&LoggerInterface $logger;
    private MockObject&EntityManager $entityManager;
    private MockObject&ZipHelper $zipHelper;
    private MockObject&FileScanner $fileScanner;
    private MockObject&UploadHandlerService $uploadHandler;
    private MockObject&ImageManipulationHandler $imageManipulationHandler;
    private MockObject&FileFactory $fileFactory;
    private WorkflowInterface $workflow;
    private MockObject&UserSignalementSubscriptionManager $userSignalementSubscriptionManager;
    private SuiviDescriptionHelper $suiviDescriptionHelper;

    protected function setUp(): void
    {
        $this->affectationManager = $this->createMock(AffectationManager::class);
        $this->suiviManager = $this->createMock(SuiviManager::class);
        $this->suiviRepository = $this->createMock(SuiviRepository::class);
        $this->interventionRepository = $this->createMock(InterventionRepository::class);
        $this->arreteRepository = $this->createMock(ArreteRepository::class);
        $this->interventionFactory = $this->createMock(InterventionFactory::class);
        $this->arreteManager = $this->createMock(ArreteManager::class);
        $this->userManager = $this->createMock(UserManager::class);
        $this->eventDispatcher = new EventDispatcher();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->zipHelper = $this->createMock(ZipHelper::class);
        $this->fileScanner = $this->createMock(FileScanner::class);
        $this->uploadHandler = $this->createMock(UploadHandlerService::class);
        $this->imageManipulationHandler = $this->createMock(ImageManipulationHandler::class);
        $this->fileFactory = $this->createMock(FileFactory::class);
        $this->workflow = static::getContainer()->get('state_machine.intervention_planning');
        $this->userSignalementSubscriptionManager = $this->createMock(UserSignalementSubscriptionManager::class);
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
        $this->interventionRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->interventionFactory
            ->expects($this->never())
            ->method('createInstanceFrom');

        $this->logger
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

        $this->arreteRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
        $this->arreteManager
            ->expects($this->once())
            ->method('createArreteFromDossierArreteSISH')
            ->with($dossierArrete, $affectation);
        $this->arreteManager
            ->expects($this->never())
            ->method('updateFromDossierArreteSISH');
        $this->entityManager
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

        $this->arreteRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($arrete);
        $this->arreteManager
            ->expects($this->once())
            ->method('updateFromDossierArreteSISH')
            ->with($arrete, $dossierArrete, $affectation);
        $this->arreteManager
            ->expects($this->never())
            ->method('createArreteFromDossierArreteSISH');
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->createEsaboraManager()->createOrUpdateArrete($affectation, $dossierArrete);
    }

    public function testCreateArreteLogsErrorWhenDateIsInvalid(): void
    {
        $dossierArrete = $this->getDossierArreteSISHCollectionResponse()->getCollection()[0];
        $affectation = $this->getAffectation(PartnerType::ARS);

        $this->arreteRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
        $this->arreteManager
            ->expects($this->once())
            ->method('createArreteFromDossierArreteSISH')
            ->willThrowException(new \InvalidArgumentException('Date Esabora invalide'));
        $this->entityManager
            ->expects($this->never())
            ->method('flush');
        $this->logger
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
        $this->interventionRepository
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
            $this->interventionFactory
                ->expects($this->once())
                ->method('createInstanceFrom');
        }

        $this->userManager
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

        $dossierResponse = $this->createMock(DossierResponseInterface::class);
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
