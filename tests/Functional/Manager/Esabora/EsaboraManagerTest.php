<?php

namespace App\Tests\Functional\Manager\Esabora;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Factory\InterventionFactory;
use App\Manager\AffectationManager;
use App\Manager\SuiviManager;
use App\Repository\InterventionRepository;
use App\Service\Esabora\Enum\EsaboraStatus;
use App\Service\Esabora\EsaboraManager;
use App\Service\Esabora\Response\DossierStateSCHSResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EsaboraManagerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private AffectationManager $affectationManager;
    private SuiviManager $suiviManager;
    private InterventionRepository $interventionRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->affectationManager = self::getContainer()->get(AffectationManager::class);
        $this->suiviManager = self::getContainer()->get(SuiviManager::class);
        $this->interventionRepository = self::getContainer()->get(InterventionRepository::class);
    }

    /**
     * @dataProvider provideDataForSynchronization
     */
    public function testAffectationSynchronizedWith(
        string $referenceSignalemeent,
        string $filename,
        string $suiviDescription,
        int $expectedAffectationStatus
    ): void {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
            'reference' => $referenceSignalemeent,
        ]);

        /** @var Affectation $affectation */
        $affectation = $signalement->getAffectations()->get(0);
        $this->assertNotEquals($expectedAffectationStatus, $affectation->getStatut());

        $basePath = __DIR__.'/../../../../tools/wiremock/src/Resources/Esabora/schs/ws_etat_dossier_sas/';
        $responseEsabora = file_get_contents($basePath.$filename);

        $dossierResponse = new DossierStateSCHSResponse(json_decode($responseEsabora, true), 200);
        $esaboraManager = new EsaboraManager(
            $this->affectationManager,
            $this->suiviManager,
            $this->interventionRepository,
            new InterventionFactory()
        );
        $esaboraManager->synchronizeAffectationFrom($dossierResponse, $affectation);

        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
            'reference' => $referenceSignalemeent,
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
    }
}
