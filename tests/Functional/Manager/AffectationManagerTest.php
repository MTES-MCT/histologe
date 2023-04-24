<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Manager\AffectationManager;
use App\Manager\SuiviManager;
use App\Service\Esabora\Enum\EsaboraStatus;
use App\Service\Esabora\Response\DossierStateResponse;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AffectationManagerTest extends KernelTestCase
{
    private const REF_SIGNALEMENT = '2022-8';
    private ManagerRegistry $managerRegistry;
    private SuiviManager $suiviManager;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->managerRegistry = self::getContainer()->get(ManagerRegistry::class);
        $this->suiviManager = self::getContainer()->get(SuiviManager::class);
        $this->logger = self::getContainer()->get(LoggerInterface::class);
    }

    public function testRemoveAllPartnersFromAffectation(): void
    {
        $affectationManager = new AffectationManager(
            $this->managerRegistry,
            $this->suiviManager,
            $this->logger,
            Affectation::class,
        );

        /** @var Signalement $signalement */
        $signalement = $this->managerRegistry->getRepository(Signalement::class)->findOneBy(
            ['reference' => self::REF_SIGNALEMENT]
        );

        $countAffectationBeforeRemove = $signalement->getAffectations()->count();
        $affectationManager->removeAffectationsFrom($signalement);
        $countAffectationAfterRemove = $signalement->getAffectations()->count();

        $this->assertNotEquals($countAffectationBeforeRemove, $countAffectationAfterRemove);
        $this->assertEquals(0, $countAffectationAfterRemove);
    }

    public function testRemoveSomePartnersFromAffectation(): void
    {
        $affectationManager = new AffectationManager(
            $this->managerRegistry,
            $this->suiviManager,
            $this->logger,
            Affectation::class,
        );

        /** @var Signalement $signalement */
        $signalement = $this->managerRegistry->getRepository(Signalement::class)->findOneBy(
            ['reference' => self::REF_SIGNALEMENT]
        );

        $partnersIdToRemove[] = $signalement->getAffectations()->get(0)->getPartner()->getId();
        $partnersIdToRemove[] = $signalement->getAffectations()->get(1)->getPartner()->getId();
        $countAffectationBeforeRemove = $signalement->getAffectations()->count();
        $affectationManager->removeAffectationsFrom(
            signalement: $signalement,
            postedPartner: [],
            partnersIdToRemove: $partnersIdToRemove
        );
        $countAffectationAfterRemove = $signalement->getAffectations()->count();
        $this->assertNotEquals($countAffectationBeforeRemove, $countAffectationAfterRemove);
        $this->assertEquals(1, $countAffectationAfterRemove);
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
        $signalement = $this->managerRegistry->getRepository(Signalement::class)->findOneBy([
            'reference' => $referenceSignalemeent,
        ]);

        /** @var Affectation $affectation */
        $affectation = $signalement->getAffectations()->get(0);
        $this->assertNotEquals($expectedAffectationStatus, $affectation->getStatut());

        $basePath = __DIR__.'/../../../tools/wiremock/src/Resources/Esabora/schs/ws_etat_dossier_sas/';
        $responseEsabora = file_get_contents($basePath.$filename);

        $dossierResponse = new DossierStateResponse(json_decode($responseEsabora, true), 200);
        $affectationManager = new AffectationManager(
            $this->managerRegistry,
            $this->suiviManager,
            $this->logger,
            Affectation::class,
        );
        $affectationManager->synchronizeAffectationFrom($dossierResponse, $affectation);

        /** @var Signalement $signalement */
        $signalement = $this->managerRegistry->getRepository(Signalement::class)->findOneBy([
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
