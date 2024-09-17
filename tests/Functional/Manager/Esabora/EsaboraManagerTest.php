<?php

namespace App\Tests\Functional\Manager\Esabora;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Factory\FileFactory;
use App\Factory\InterventionFactory;
use App\Manager\AffectationManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\InterventionRepository;
use App\Service\Esabora\Enum\EsaboraStatus;
use App\Service\Esabora\EsaboraManager;
use App\Service\Esabora\Response\DossierStateSCHSResponse;
use App\Service\Esabora\Response\DossierStateSISHResponse;
use App\Service\Files\ZipHelper;
use App\Service\ImageManipulationHandler;
use App\Service\Security\FileScanner;
use App\Service\UploadHandlerService;
use App\Tests\FixturesHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
    private ParameterBagInterface $parameterBag;
    private ZipHelper $zipHelper;
    private FileScanner $fileScanner;
    private UploadHandlerService $uploadHander;
    private ImageManipulationHandler $imageManipulationHandler;
    private UrlGeneratorInterface $UrlGeneratorInterface;
    private FileFactory $fileFactory;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->affectationManager = self::getContainer()->get(AffectationManager::class);
        $this->suiviManager = self::getContainer()->get(SuiviManager::class);
        $this->interventionRepository = self::getContainer()->get(InterventionRepository::class);
        $this->eventDispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        $this->userManager = self::getContainer()->get(UserManager::class);
        $this->logger = self::getContainer()->get(LoggerInterface::class);
        $this->parameterBag = self::getContainer()->get(ParameterBagInterface::class);
        $this->zipHelper = self::getContainer()->get(ZipHelper::class);
        $this->fileScanner = self::getContainer()->get(FileScanner::class);
        $this->uploadHander = self::getContainer()->get(UploadHandlerService::class);
        $this->imageManipulationHandler = self::getContainer()->get(ImageManipulationHandler::class);
        $this->UrlGeneratorInterface = self::getContainer()->get('router');
        $this->fileFactory = self::getContainer()->get(FileFactory::class);
    }

    /**
     * @dataProvider provideDataForSynchronization
     */
    public function testAffectationSynchronizedWith(
        string $referenceSignalement,
        string $filename,
        string $suiviDescription,
        int $expectedAffectationStatus,
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
            $this->eventDispatcher,
            $this->userManager,
            $this->logger,
            $this->parameterBag,
            $this->entityManager,
            $this->zipHelper,
            $this->fileScanner,
            $this->uploadHander,
            $this->imageManipulationHandler,
            $this->UrlGeneratorInterface,
            $this->fileFactory,
        );

        $esaboraManager->synchronizeAffectationFrom($dossierResponse, $affectation);

        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
            'reference' => $referenceSignalement,
        ]);

        /** @var Suivi $suivi */
        $suivi = $signalement->getSuivis()->last();
        $this->assertStringContainsString($suiviDescription, $suivi->getDescription());
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
            'remis en attente',
            Affectation::STATUS_WAIT,
            Suivi::TYPE_TECHNICAL,
            false, // suivi mail not sent cause suivi techical
        ];

        yield EsaboraStatus::ESABORA_ACCEPTED->value => [
            '2022-1',
            'etat_importe.json',
            'accepté via Esabora',
            Affectation::STATUS_ACCEPTED,
            Suivi::TYPE_AUTO,
            true, // suivi mail sent
        ];

        yield EsaboraStatus::ESABORA_CLOSED->value => [
            '2022-10',
            'etat_termine.json',
            'cloturé via Esabora',
            Affectation::STATUS_CLOSED,
            Suivi::TYPE_AUTO,
            true, // suivi mail sent
        ];

        yield EsaboraStatus::ESABORA_REFUSED->value => [
            '2022-2',
            'etat_non_importe.json',
            'refusé via Esabora',
            Affectation::STATUS_REFUSED,
            Suivi::TYPE_AUTO,
            false, // suivi mail not sent cause signalement closed
        ];

        yield EsaboraStatus::ESABORA_REJECTED->value.' SISH' => [
            '2022-2',
            '../../sish/ws_etat_dossier_sas/etat_rejete.json',
            'refusé via SI-Santé Habitat (SI-SH) pour motif suivant:',
            Affectation::STATUS_REFUSED,
            Suivi::TYPE_AUTO,
            false, // suivi mail not sent cause signalement closed
        ];

        yield EsaboraStatus::ESABORA_ACCEPTED->value.' SISH' => [
            '2022-2',
            '../../sish/ws_etat_dossier_sas/etat_importe.json',
            'accepté via SI-Santé Habitat (SI-SH) (Dossier 2023/SISH/0010)',
            Affectation::STATUS_ACCEPTED,
            Suivi::TYPE_AUTO,
            false, // suivi mail not sent cause signalement closed
        ];
    }

    public function testAffectationSynchronizedTwoTimesWithoutChanges(
    ): void {
        $referenceSignalement = '2022-2';
        $filename = '../../sish/ws_etat_dossier_sas/etat_importe.json';
        $suiviDescription = 'accepté via SI-Santé Habitat (SI-SH) (Dossier 2023/SISH/0010)';
        $expectedAffectationStatus = Affectation::STATUS_ACCEPTED;
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
            $this->eventDispatcher,
            $this->userManager,
            $this->logger,
            $this->parameterBag,
            $this->entityManager,
            $this->zipHelper,
            $this->fileScanner,
            $this->uploadHander,
            $this->imageManipulationHandler,
            $this->UrlGeneratorInterface,
            $this->fileFactory,
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
}
