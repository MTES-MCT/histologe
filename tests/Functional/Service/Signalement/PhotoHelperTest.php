<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\Enum\DocumentType;
use App\Factory\SignalementAffectationListViewFactory;
use App\Factory\SignalementExportFactory;
use App\Factory\SignalementFactory;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Repository\BailleurRepository;
use App\Repository\DesordrePrecisionRepository;
use App\Repository\Query\SignalementList\ExportIterableQuery;
use App\Repository\Query\SignalementList\ListPaginatorQuery;
use App\Service\Signalement\CriticiteCalculator;
use App\Service\Signalement\DesordreTraitement\DesordreCompositionLogementLoader;
use App\Service\Signalement\PhotoHelper;
use App\Service\Signalement\Qualification\QualificationStatusService;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use App\Service\Signalement\SignalementAddressUpdater;
use App\Service\Signalement\ZipcodeProvider;
use App\Specification\Signalement\SuroccupationSpecification;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

class PhotoHelperTest extends KernelTestCase
{
    private Security $security;
    private ManagerRegistry $managerRegistry;
    private SignalementFactory $signalementFactory;
    private EventDispatcherInterface $eventDispatcher;
    private QualificationStatusService $qualificationStatusService;
    private SignalementAffectationListViewFactory $signalementAffectationListViewFactory;
    private SignalementExportFactory $signalementExportFactory;
    private SignalementManager $signalementManager;
    private SuroccupationSpecification $suroccupationSpecification;
    private CriticiteCalculator $criticiteCalculator;
    private SignalementQualificationUpdater $signalementQualificationUpdater;
    private DesordrePrecisionRepository $desordrePrecisionRepository;
    private DesordreCompositionLogementLoader $desordreCompositionLogementLoader;
    private SuiviManager $suiviManager;
    private BailleurRepository $bailleurRepository;
    private SignalementAddressUpdater $signalementAddressUpdater;
    private AffectationManager $affectationManager;
    private ZipcodeProvider $zipcodeProvider;
    private HtmlSanitizerInterface $htmlSanitizerInterface;

    private ExportIterableQuery $exportIterableQuery;
    private ListPaginatorQuery $listPaginatorQuery;

    protected function setUp(): void
    {
        $this->managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        $this->security = static::getContainer()->get('security.helper');
        $this->signalementFactory = static::getContainer()->get(SignalementFactory::class);
        $this->eventDispatcher = static::getContainer()->get(EventDispatcherInterface::class);
        /* @var QualificationStatusService $qualificationStatusService */
        $this->qualificationStatusService = static::getContainer()->get(QualificationStatusService::class);
        $this->signalementAffectationListViewFactory = static::getContainer()->get(
            SignalementAffectationListViewFactory::class
        );
        $this->signalementExportFactory = static::getContainer()->get(SignalementExportFactory::class);
        $this->suroccupationSpecification = static::getContainer()->get(SuroccupationSpecification::class);
        $this->criticiteCalculator = static::getContainer()->get(CriticiteCalculator::class);
        $this->signalementQualificationUpdater = static::getContainer()->get(SignalementQualificationUpdater::class);
        $this->desordrePrecisionRepository = static::getContainer()->get(DesordrePrecisionRepository::class);
        $this->desordreCompositionLogementLoader = static::getContainer()->get(DesordreCompositionLogementLoader::class);
        $this->suiviManager = static::getContainer()->get(SuiviManager::class);
        $this->bailleurRepository = static::getContainer()->get(BailleurRepository::class);
        $this->signalementAddressUpdater = static::getContainer()->get(SignalementAddressUpdater::class);
        $this->affectationManager = static::getContainer()->get(AffectationManager::class);
        $this->zipcodeProvider = static::getContainer()->get(ZipcodeProvider::class);
        $this->exportIterableQuery = static::getContainer()->get(ExportIterableQuery::class);
        $this->listPaginatorQuery = static::getContainer()->get(ListPaginatorQuery::class);
        $this->htmlSanitizerInterface = self::getContainer()->get('html_sanitizer.sanitizer.app.message_sanitizer');

        $this->signalementManager = new SignalementManager(
            $this->managerRegistry,
            $this->security,
            $this->signalementFactory,
            $this->eventDispatcher,
            $this->qualificationStatusService,
            $this->signalementAffectationListViewFactory,
            $this->signalementExportFactory,
            $this->suroccupationSpecification,
            $this->criticiteCalculator,
            $this->signalementQualificationUpdater,
            $this->desordrePrecisionRepository,
            $this->desordreCompositionLogementLoader,
            $this->suiviManager,
            $this->bailleurRepository,
            $this->affectationManager,
            $this->signalementAddressUpdater,
            $this->zipcodeProvider,
            $this->exportIterableQuery,
            $this->listPaginatorQuery,
            $this->htmlSanitizerInterface
        );
    }

    public function testGetPhotosBySlug(): void
    {
        $signalement = $this->signalementManager->findOneBy(['reference' => '2023-27']);

        $desordrePrecisionSlug = 'desordres_batiment_proprete_interieur';
        $photos = PhotoHelper::getPhotosBySlug($signalement, $desordrePrecisionSlug);
        $this->assertCount(1, $photos);
        $firstKey = array_keys($photos)[0];
        $this->assertEquals(DocumentType::PHOTO_SITUATION, $photos[$firstKey]->getDocumentType());
        $this->assertEquals('Capture-d-ecran-du-2023-06-13-12-58-43-648b2a6b9730f.png', $photos[$firstKey]->getTitle());

        $desordrePrecisionSlug = 'desordres_batiment_isolation_murs';
        $photos = PhotoHelper::getPhotosBySlug($signalement, $desordrePrecisionSlug);
        $this->assertCount(0, $photos);
    }

    public function testGetSortedPhotos(): void
    {
        $signalement = $this->signalementManager->findOneBy(['reference' => '2023-27']);

        $photos = PhotoHelper::getSortedPhotos($signalement);
        $this->assertCount(3, $photos);
        $this->assertEquals(DocumentType::PHOTO_SITUATION, $photos[0]->getDocumentType());
        $this->assertEquals('Capture-d-ecran-du-2023-06-13-12-58-43-648b2a6b9730f.png', $photos[0]->getTitle());
        $this->assertEquals(DocumentType::PHOTO_SITUATION, $photos[1]->getDocumentType());
        $this->assertEquals(DocumentType::AUTRE, $photos[2]->getDocumentType());
    }
}
