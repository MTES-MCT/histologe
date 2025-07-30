<?php

namespace App\Tests\Unit\Service\Esabora;

use App\Entity\Affectation;
use App\Entity\Enum\InterventionType;
use App\Entity\Enum\PartnerType;
use App\Entity\Intervention;
use App\Entity\User;
use App\Factory\FileFactory;
use App\Factory\InterventionFactory;
use App\Manager\AffectationManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\InterventionRepository;
use App\Service\Files\ZipHelper;
use App\Service\ImageManipulationHandler;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Security\FileScanner;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use App\Service\TimezoneProvider;
use App\Service\UploadHandlerService;
use App\Tests\FixturesHelper;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

class EsaboraManagerTest extends KernelTestCase
{
    use FixturesHelper;
    protected const string CREATE_ACTION = 'create';
    protected const string UPDATE_ACTION = 'update';

    protected MockObject|AffectationManager $affectationManager;
    protected MockObject|SuiviManager $suiviManager;
    protected MockObject|InterventionRepository $interventionRepository;
    protected MockObject|InterventionFactory $interventionFactory;
    protected MockObject|EventDispatcherInterface $eventDispatcher;
    protected MockObject|UserManager $userManager;
    private MockObject|LoggerInterface $logger;
    private MockObject|EntityManager $entityManager;
    private MockObject|ZipHelper $zipHelper;
    private MockObject|FileScanner $fileScanner;
    private MockObject|UploadHandlerService $uploadHander;
    private MockObject|ImageManipulationHandler $imageManipulationHandler;
    private MockObject|FileFactory $fileFactory;
    private MockObject|SignalementQualificationUpdater $signalementQualificationUpdater;
    private HtmlSanitizerInterface $htmlSanitizerInterface;

    protected function setUp(): void
    {
        $this->affectationManager = $this->createMock(AffectationManager::class);
        $this->suiviManager = $this->createMock(SuiviManager::class);
        $this->interventionRepository = $this->createMock(InterventionRepository::class);
        $this->interventionFactory = $this->createMock(InterventionFactory::class);
        $this->userManager = $this->createMock(UserManager::class);
        $this->eventDispatcher = new EventDispatcher();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->zipHelper = $this->createMock(ZipHelper::class);
        $this->fileScanner = $this->createMock(FileScanner::class);
        $this->uploadHander = $this->createMock(UploadHandlerService::class);
        $this->imageManipulationHandler = $this->createMock(ImageManipulationHandler::class);
        $this->fileFactory = $this->createMock(FileFactory::class);
        $this->signalementQualificationUpdater = $this->createMock(SignalementQualificationUpdater::class);
        $this->htmlSanitizerInterface = self::getContainer()->get('html_sanitizer.sanitizer.app.message_sanitizer');
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

        $this->interventionRepository
            ->expects($this->never())
            ->method('save');

        $this->logger
            ->expects($this->once())
            ->method('error');

        $this->userManager
            ->expects($this->once())
            ->method('getSystemUser')
            ->willReturn($this->getUser([User::ROLE_ADMIN]));

        $esaboraManager = new EsaboraManager(
            $this->affectationManager,
            $this->suiviManager,
            $this->interventionRepository,
            $this->interventionFactory,
            $this->eventDispatcher, // @phpstan-ignore-line
            $this->userManager,
            $this->logger,
            $this->entityManager,
            $this->zipHelper,
            $this->fileScanner,
            $this->uploadHander,
            $this->imageManipulationHandler,
            $this->fileFactory,
            $this->signalementQualificationUpdater,
            $this->htmlSanitizerInterface
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
        $dossierArreteCollection = $this->getDossierArreteSISHCollectionResponse()->getCollection();
        $dossierArrete = $dossierArreteCollection[0];
        $esaboraManager = $this->provideEsaboraManagerForIntervention(self::CREATE_ACTION, InterventionType::ARRETE_PREFECTORAL);
        $esaboraManager->createOrUpdateArrete($this->getAffectation(PartnerType::ARS), $dossierArrete);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateArrete(): void
    {
        $dossierArreteCollection = $this->getDossierArreteSISHCollectionResponse()->getCollection();
        $dossierArrete = $dossierArreteCollection[0];
        $esaboraManager = $this->provideEsaboraManagerForIntervention(self::UPDATE_ACTION, InterventionType::ARRETE_PREFECTORAL);
        $esaboraManager->createOrUpdateArrete($this->getAffectation(PartnerType::ARS), $dossierArrete);
    }

    public function provideEsaboraManagerForIntervention(string $action, InterventionType $interventionType): EsaboraManager
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

        $this->interventionRepository
            ->expects($this->once())
            ->method('save');

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
            $this->interventionRepository,
            $this->interventionFactory,
            $this->eventDispatcher, // @phpstan-ignore-line
            $this->userManager,
            $this->logger,
            $this->entityManager,
            $this->zipHelper,
            $this->fileScanner,
            $this->uploadHander,
            $this->imageManipulationHandler,
            $this->fileFactory,
            $this->signalementQualificationUpdater,
            $this->htmlSanitizerInterface
        );
    }

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

        $signalement->setTerritory($territory);

        $affectation = new Affectation();
        $affectation->setSignalement($signalement);

        $dossierVisiteCollection = $this->getDossierVisiteSISHCollectionResponse()->getCollection();
        $dossierVisite = $dossierVisiteCollection[0];

        $this->interventionRepository
            ->expects($this->once())
            ->method('save')
            ->with($intervention, true);

        $this->userManager
        ->expects($this->once())
        ->method('getSystemUser')
        ->willReturn($this->getUser([User::ROLE_ADMIN]));
        $esaboraManager = new EsaboraManager(
            $this->affectationManager,
            $this->suiviManager,
            $this->interventionRepository,
            $this->interventionFactory,
            $this->eventDispatcher, // @phpstan-ignore-line
            $this->userManager,
            $this->logger,
            $this->entityManager,
            $this->zipHelper,
            $this->fileScanner,
            $this->uploadHander,
            $this->imageManipulationHandler,
            $this->fileFactory,
            $this->signalementQualificationUpdater,
            $this->htmlSanitizerInterface
        );

        $reflector = new \ReflectionClass($esaboraManager);
        $method = $reflector->getMethod('updateFromDossierVisite');
        $method->setAccessible(true);
        $result = $method->invoke($esaboraManager, $intervention, $dossierVisite, $affectation);

        $this->assertTrue($result);
        $this->assertEquals(new \DateTimeImmutable('2023-05-03 08:16:00'), $intervention->getScheduledAt());
        $this->assertEquals('SH', $intervention->getDoneBy());
        $this->assertEquals('SH', $intervention->getExternalOperator());
    }

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

        $signalement->setTerritory($territory);

        $affectation = new Affectation();
        $affectation->setSignalement($signalement);

        $dossierVisiteCollection = $this->getDossierVisiteSISHCollectionResponse()->getCollection();
        $dossierVisite = $dossierVisiteCollection[0];

        $this->interventionRepository
            ->expects($this->never())
            ->method('save');

        $this->userManager
        ->expects($this->once())
        ->method('getSystemUser')
        ->willReturn($this->getUser([User::ROLE_ADMIN]));
        $esaboraManager = new EsaboraManager(
            $this->affectationManager,
            $this->suiviManager,
            $this->interventionRepository,
            $this->interventionFactory,
            $this->eventDispatcher, // @phpstan-ignore-line
            $this->userManager,
            $this->logger,
            $this->entityManager,
            $this->zipHelper,
            $this->fileScanner,
            $this->uploadHander,
            $this->imageManipulationHandler,
            $this->fileFactory,
            $this->signalementQualificationUpdater,
            $this->htmlSanitizerInterface
        );

        $reflector = new \ReflectionClass($esaboraManager);
        $method = $reflector->getMethod('updateFromDossierVisite');
        $method->setAccessible(true);
        $result = $method->invoke($esaboraManager, $intervention, $dossierVisite, $affectation);

        $this->assertFalse($result);
        $this->assertEquals(new \DateTimeImmutable('2023-05-03 08:16:00'), $intervention->getScheduledAt());
        $this->assertEquals('SH', $intervention->getDoneBy());
        $this->assertEquals('SH', $intervention->getExternalOperator());
    }
}
