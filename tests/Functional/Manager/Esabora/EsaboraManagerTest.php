<?php

namespace App\Tests\Functional\Manager\Esabora;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Factory\InterventionFactory;
use App\Manager\AffectationManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\InterventionRepository;
use App\Service\Esabora\Enum\EsaboraStatus;
use App\Service\Esabora\EsaboraManager;
use App\Service\Esabora\Response\DossierStateSCHSResponse;
use App\Service\Esabora\Response\DossierStateSISHResponse;
use App\Tests\FixturesHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

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
    }

    /**
     * @dataProvider provideDataForSynchronization
     */
    public function testAffectationSynchronizedWith(
        string $referenceSignalement,
        string $filename,
        string $suiviDescription,
        int $expectedAffectationStatus
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
        $dossierResponse = str_contains($filename, 'etat_rejete')
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
        $this->assertEquals(Suivi::TYPE_AUTO, $suivi->getType());

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
        ];

        yield EsaboraStatus::ESABORA_ACCEPTED->value => [
            '2022-1',
            'etat_importe.json',
            'accepté via Esabora',
            Affectation::STATUS_ACCEPTED,
        ];

        yield EsaboraStatus::ESABORA_CLOSED->value => [
            '2022-10',
            'etat_termine.json',
            'cloturé via Esabora',
            Affectation::STATUS_CLOSED,
        ];

        yield EsaboraStatus::ESABORA_REFUSED->value => [
            '2022-2',
            'etat_non_importe.json',
            'refusé via Esabora',
            Affectation::STATUS_REFUSED,
        ];

        yield EsaboraStatus::ESABORA_REJECTED->value => [
            '2022-2',
            '../../sish/ws_etat_dossier_sas/etat_rejete.json',
            'refusé via Esabora pour motif suivant:',
            Affectation::STATUS_REFUSED,
        ];
    }
}
