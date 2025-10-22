<?php

namespace App\Tests\Functional\Manager\Esabora;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Factory\FileFactory;
use App\Factory\InterventionFactory;
use App\Manager\AffectationManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Manager\UserSignalementSubscriptionManager;
use App\Repository\InterventionRepository;
use App\Service\Files\ZipHelper;
use App\Service\ImageManipulationHandler;
use App\Service\Interconnection\Esabora\Enum\EsaboraStatus;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Interconnection\Esabora\Response\DossierStateSCHSResponse;
use App\Service\Interconnection\Esabora\Response\DossierStateSISHResponse;
use App\Service\Security\FileScanner;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use App\Service\UploadHandlerService;
use App\Tests\FixturesHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class EsaboraManagerTest extends KernelTestCase
{
    use FixturesHelper;

    private EntityManagerInterface $entityManager;
    private AffectationManager $affectationManager;
    private SuiviManager $suiviManager;
    private InterventionRepository $interventionRepository;
    private EventDispatcherInterface $eventDispatcher;
    private UserManager $userManager;
    private LoggerInterface $logger;
    private ZipHelper $zipHelper;
    private FileScanner $fileScanner;
    private UploadHandlerService $uploadHander;
    private ImageManipulationHandler $imageManipulationHandler;
    private FileFactory $fileFactory;
    private SignalementQualificationUpdater $signalementQualificationUpdater;
    private HtmlSanitizerInterface $htmlSanitizerInterface;
    private WorkflowInterface $workflow;
    private UserSignalementSubscriptionManager $userSignalementSubscriptionManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
        $this->affectationManager = self::getContainer()->get(AffectationManager::class);
        $this->suiviManager = self::getContainer()->get(SuiviManager::class);
        $this->interventionRepository = self::getContainer()->get(InterventionRepository::class);
        $this->eventDispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        $this->userManager = self::getContainer()->get(UserManager::class);
        $this->logger = self::getContainer()->get(LoggerInterface::class);
        $this->zipHelper = self::getContainer()->get(ZipHelper::class);
        $this->fileScanner = self::getContainer()->get(FileScanner::class);
        $this->uploadHander = self::getContainer()->get(UploadHandlerService::class);
        $this->imageManipulationHandler = self::getContainer()->get(ImageManipulationHandler::class);
        $this->fileFactory = self::getContainer()->get(FileFactory::class);
        $this->signalementQualificationUpdater = self::getContainer()->get(SignalementQualificationUpdater::class);
        $this->htmlSanitizerInterface = self::getContainer()->get('html_sanitizer.sanitizer.app.message_sanitizer');
        $this->workflow = self::getContainer()->get('state_machine.intervention_planning');
        $this->userSignalementSubscriptionManager = self::getContainer()->get(UserSignalementSubscriptionManager::class);
    }

    /**
     * @dataProvider provideDataForSynchronization
     */
    public function testAffectationSynchronizedWith(
        string $referenceSignalement,
        string $filename,
        string $suiviDescription,
        AffectationStatus $expectedAffectationStatus,
        int $suiviStatus,
        bool $mailSent,
    ): void {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
            'reference' => $referenceSignalement,
        ]);

        /** @var Affectation $affectation */
        $affectation = $signalement->getAffectations()->get(0);
        $this->assertNotEquals($expectedAffectationStatus, $affectation->getStatut());

        $basePath = __DIR__.'/../../../../tools/wiremock/src/Resources/Esabora/schs/ws_etat_dossier_sas/';
        $responseEsabora = file_get_contents($basePath.$filename);
        $dossierResponse = str_contains($filename, 'sish')
                ? new DossierStateSISHResponse(json_decode($responseEsabora, true), 200)
                : new DossierStateSCHSResponse(json_decode($responseEsabora, true), 200);

        $esaboraManager = new EsaboraManager(
            $this->affectationManager,
            $this->suiviManager,
            $this->interventionRepository,
            new InterventionFactory(),
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
            $this->htmlSanitizerInterface,
            $this->workflow,
            $this->userSignalementSubscriptionManager,
        );

        $esaboraManager->synchronizeAffectationFrom($dossierResponse, $affectation);

        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
            'reference' => $referenceSignalement,
        ]);

        /** @var Suivi $suivi */
        $suivi = $signalement->getSuivis()->last();
        $this->assertStringContainsString($suiviDescription, $suivi->getDescription(), $suiviDescription);
        $this->assertFalse($suivi->getIsPublic());
        $this->assertEquals($suiviStatus, $suivi->getType());
        $this->assertEmailCount($mailSent ? 1 : 0);

        /** @var Affectation $affectationUpdated */
        $affectationUpdated = $signalement->getAffectations()->get(0);
        $this->assertEquals($expectedAffectationStatus, $affectationUpdated->getStatut());
    }

    public function provideDataForSynchronization(): \Generator
    {
        yield EsaboraStatus::ESABORA_WAIT->value => [
            '2022-8',
            'etat_a_traiter.json',
            'remis en attente par Partenaire 13-02 via Esabora',
            AffectationStatus::WAIT,
            Suivi::TYPE_TECHNICAL,
            false, // suivi mail not sent cause suivi technical
        ];

        yield EsaboraStatus::ESABORA_ACCEPTED->value => [
            '2022-1',
            'etat_importe.json',
            'accepté par Partenaire 13-01 via Esabora',
            AffectationStatus::ACCEPTED,
            Suivi::TYPE_AUTO,
            true, // suivi mail sent
        ];

        yield EsaboraStatus::ESABORA_CLOSED->value => [
            '2022-10',
            'etat_termine.json',
            'cloturé par Partenaire 13-02 via Esabora',
            AffectationStatus::CLOSED,
            Suivi::TYPE_AUTO,
            true, // suivi mail sent
        ];

        yield EsaboraStatus::ESABORA_REFUSED->value => [
            '2022-2',
            'etat_non_importe.json',
            'refusé par Partenaire 01-01 via Esabora',
            AffectationStatus::REFUSED,
            Suivi::TYPE_AUTO,
            false, // suivi mail not sent cause signalement closed
        ];

        yield EsaboraStatus::ESABORA_REJECTED->value.' SISH' => [
            '2022-2',
            '../../sish/ws_etat_dossier_sas/etat_rejete.json',
            'refusé via '.EsaboraSISHService::NAME_SI.' pour motif suivant:',
            AffectationStatus::REFUSED,
            Suivi::TYPE_AUTO,
            false, // suivi mail not sent cause signalement closed
        ];

        yield EsaboraStatus::ESABORA_ACCEPTED->value.' SISH' => [
            '2022-2',
            '../../sish/ws_etat_dossier_sas/etat_importe.json',
            'accepté par Partenaire 01-01 via '.EsaboraSISHService::NAME_SI.' (Dossier 2023/SISH/0010)',
            AffectationStatus::ACCEPTED,
            Suivi::TYPE_AUTO,
            false, // suivi mail not sent cause signalement closed
        ];
    }

    public function testAffectationSynchronizedTwoTimesWithoutChanges(
    ): void {
        $referenceSignalement = '2022-2';
        $filename = '../../sish/ws_etat_dossier_sas/etat_importe.json';
        $suiviDescription = 'accepté par Partenaire 01-01 via '.EsaboraSISHService::NAME_SI.' (Dossier 2023/SISH/0010)';
        $expectedAffectationStatus = AffectationStatus::ACCEPTED;
        $suiviStatus = Suivi::TYPE_AUTO;
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
            'reference' => $referenceSignalement,
        ]);

        $this->assertEquals(2, \count($signalement->getSuivis()));
        /** @var Affectation $affectation */
        $affectation = $signalement->getAffectations()->get(0);
        $this->assertNotEquals($expectedAffectationStatus, $affectation->getStatut());

        $basePath = __DIR__.'/../../../../tools/wiremock/src/Resources/Esabora/schs/ws_etat_dossier_sas/';
        $responseEsabora = file_get_contents($basePath.$filename);
        $dossierResponse = str_contains($filename, 'sish')
                ? new DossierStateSISHResponse(json_decode($responseEsabora, true), 200)
                : new DossierStateSCHSResponse(json_decode($responseEsabora, true), 200);

        $esaboraManager = new EsaboraManager(
            $this->affectationManager,
            $this->suiviManager,
            $this->interventionRepository,
            new InterventionFactory(),
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
            $this->htmlSanitizerInterface,
            $this->workflow,
            $this->userSignalementSubscriptionManager,
        );

        $esaboraManager->synchronizeAffectationFrom($dossierResponse, $affectation);
        $this->entityManager->refresh($signalement);

        /** @var Suivi $suivi */
        $suivi = $signalement->getSuivis()->last();
        $this->assertEquals(3, \count($signalement->getSuivis()));
        $this->assertStringContainsString($suiviDescription, $suivi->getDescription());
        $this->assertFalse($suivi->getIsPublic());
        $this->assertEquals($suiviStatus, $suivi->getType());

        /** @var Affectation $affectationUpdated */
        $affectationUpdated = $signalement->getAffectations()->get(0);
        $this->assertEquals($expectedAffectationStatus, $affectationUpdated->getStatut());

        // on appelle une deuxième fois la synchronisation sans changement
        $esaboraManager->synchronizeAffectationFrom($dossierResponse, $affectation);
        $this->entityManager->refresh($signalement);

        // on vérifie qu'aucun suivi n'a été créé
        $this->assertEquals(3, \count($signalement->getSuivis()));
    }

    /**
     * Vérifie que le workflow 'confirm' est appliqué lors de la création d'une intervention avec une date passée.
     */
    public function testCreateVisiteWithPastDateAppliesConfirmTransition(): void
    {
        $referenceSignalement = '2022-2';
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
            'reference' => $referenceSignalement,
        ]);
        $esaboraManager = new EsaboraManager(
            $this->affectationManager,
            $this->suiviManager,
            $this->interventionRepository,
            new InterventionFactory(),
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
            $this->htmlSanitizerInterface,
            $this->workflow,
            $this->userSignalementSubscriptionManager,
        );

        $dossierVisite = $this->getDossierVisiteSISHCollectionResponse()->getCollection()[0];
        $affectation = $signalement->getAffectations()->first();

        $esaboraManager->createOrUpdateVisite($affectation, $dossierVisite);
        $this->entityManager->flush();
        $this->entityManager->refresh($signalement);
        $suivi = $signalement->getSuivis()->last();
        $intervention = $signalement->getInterventions()->first();

        $this->assertStringContainsString('Visite de contrôle réalisée', $suivi->getDescription());
        $this->assertEquals(Intervention::STATUS_DONE, $intervention->getStatus(), 'Le statut doit être DONE après application du workflow confirm');
    }
}
