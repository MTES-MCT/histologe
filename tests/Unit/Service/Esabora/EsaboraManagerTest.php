<?php

namespace App\Tests\Unit\Service\Esabora;

use App\Entity\Enum\PartnerType;
use App\Entity\Intervention;
use App\Factory\InterventionFactory;
use App\Manager\AffectationManager;
use App\Manager\SuiviManager;
use App\Repository\InterventionRepository;
use App\Service\Esabora\EsaboraManager;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EsaboraManagerTest extends TestCase
{
    use FixturesHelper;

    protected MockObject|AffectationManager $affectationManager;
    protected MockObject|SuiviManager $suiviManager;
    protected MockObject|InterventionRepository $interventionRepository;
    protected MockObject|InterventionFactory $interventionFactory;
    private MockObject|LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->affectationManager = $this->createMock(AffectationManager::class);
        $this->suiviManager = $this->createMock(SuiviManager::class);
        $this->interventionRepository = $this->createMock(InterventionRepository::class);
        $this->interventionFactory = $this->createMock(InterventionFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testCreateVisite(): void
    {
        $dossierVisiteCollection = $this->getDossierVisiteSISHCollectionResponse()->getCollection();
        $dossierVisite = $dossierVisiteCollection[0];

        $this->interventionRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->interventionFactory
            ->expects($this->once())
            ->method('createInstanceFrom');

        $this->interventionRepository
            ->expects($this->once())
            ->method('save');

        $esaboraManager = new EsaboraManager(
            $this->affectationManager,
            $this->suiviManager,
            $this->interventionRepository,
            $this->interventionFactory,
            $this->logger,
        );
        $esaboraManager->createOrUpdateVisite($this->getAffectation(PartnerType::ARS), $dossierVisite);
    }

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

        $esaboraManager = new EsaboraManager(
            $this->affectationManager,
            $this->suiviManager,
            $this->interventionRepository,
            $this->interventionFactory,
            $this->logger,
        );
        $esaboraManager->createOrUpdateVisite($this->getAffectation(PartnerType::ARS), $dossierVisite);
    }

    public function testUpdateVisite(): void
    {
        $dossierVisiteCollection = $this->getDossierVisiteSISHCollectionResponse()->getCollection();
        $dossierVisite = $dossierVisiteCollection[0];

        $this->interventionRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(new Intervention());

        $this->interventionFactory
            ->expects($this->any())
            ->method('createInstanceFrom');

        $esaboraManager = new EsaboraManager(
            $this->affectationManager,
            $this->suiviManager,
            $this->interventionRepository,
            $this->interventionFactory,
            $this->logger,
        );
        $esaboraManager->createOrUpdateVisite($this->getAffectation(PartnerType::ARS), $dossierVisite);
    }

    public function testCreateArrete(): void
    {
        $dossierArreteCollection = $this->getDossierArreteSISHCollectionResponse()->getCollection();
        $dossierArrete = $dossierArreteCollection[0];

        $this->interventionRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->interventionFactory
            ->expects($this->once())
            ->method('createInstanceFrom');

        $this->interventionRepository
            ->expects($this->once())
            ->method('save');

        $esaboraManager = new EsaboraManager(
            $this->affectationManager,
            $this->suiviManager,
            $this->interventionRepository,
            $this->interventionFactory,
            $this->logger
        );
        $esaboraManager->createOrUpdateArrete($this->getAffectation(PartnerType::ARS), $dossierArrete);
    }

    public function testUpdateArrete(): void
    {
        $dossierArreteCollection = $this->getDossierArreteSISHCollectionResponse()->getCollection();
        $dossierArrete = $dossierArreteCollection[0];

        $this->interventionRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(new Intervention());

        $this->interventionFactory
            ->expects($this->any())
            ->method('createInstanceFrom');

        $esaboraManager = new EsaboraManager(
            $this->affectationManager,
            $this->suiviManager,
            $this->interventionRepository,
            $this->interventionFactory,
            $this->logger
        );
        $esaboraManager->createOrUpdateArrete($this->getAffectation(PartnerType::ARS), $dossierArrete);
    }
}
