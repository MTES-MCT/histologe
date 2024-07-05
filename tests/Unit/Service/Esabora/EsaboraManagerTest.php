<?php

namespace App\Tests\Unit\Service\Esabora;

use App\Entity\Enum\PartnerType;
use App\Entity\Intervention;
use App\Entity\User;
use App\Factory\InterventionFactory;
use App\Manager\AffectationManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\InterventionRepository;
use App\Service\Esabora\EsaboraManager;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EsaboraManagerTest extends TestCase
{
    use FixturesHelper;
    protected const CREATE_ACTION = 'create';
    protected const UPDATE_ACTION = 'update';

    protected MockObject|AffectationManager $affectationManager;
    protected MockObject|SuiviManager $suiviManager;
    protected MockObject|InterventionRepository $interventionRepository;
    protected MockObject|InterventionFactory $interventionFactory;
    protected MockObject|EventDispatcherInterface $eventDispatcher;
    protected MockObject|UserManager $userManager;
    private MockObject|LoggerInterface $logger;
    private MockObject|ParameterBagInterface $parameterBag;

    protected function setUp(): void
    {
        $this->affectationManager = $this->createMock(AffectationManager::class);
        $this->suiviManager = $this->createMock(SuiviManager::class);
        $this->interventionRepository = $this->createMock(InterventionRepository::class);
        $this->interventionFactory = $this->createMock(InterventionFactory::class);
        $this->userManager = $this->createMock(UserManager::class);
        $this->eventDispatcher = new EventDispatcher();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
    }

    public function testCreateVisite(): void
    {
        $dossierVisiteCollection = $this->getDossierVisiteSISHCollectionResponse()->getCollection();
        $dossierVisite = $dossierVisiteCollection[0];
        $esaboraManager = $this->provideEsaboraManagerForIntervention(self::CREATE_ACTION);
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
            $this->eventDispatcher,
            $this->userManager,
            $this->logger,
            $this->parameterBag,
        );
        $esaboraManager->createOrUpdateVisite($this->getAffectation(PartnerType::ARS), $dossierVisite);
    }

    public function testUpdateVisite(): void
    {
        $dossierVisiteCollection = $this->getDossierVisiteSISHCollectionResponse()->getCollection();
        $dossierVisite = $dossierVisiteCollection[0];
        $esaboraManager = $this->provideEsaboraManagerForIntervention(self::UPDATE_ACTION);
        $esaboraManager->createOrUpdateVisite($this->getAffectation(PartnerType::ARS), $dossierVisite);
    }

    public function testCreateArrete(): void
    {
        $dossierArreteCollection = $this->getDossierArreteSISHCollectionResponse()->getCollection();
        $dossierArrete = $dossierArreteCollection[0];
        $esaboraManager = $this->provideEsaboraManagerForIntervention(self::CREATE_ACTION);
        $esaboraManager->createOrUpdateArrete($this->getAffectation(PartnerType::ARS), $dossierArrete);
    }

    public function testUpdateArrete(): void
    {
        $dossierArreteCollection = $this->getDossierArreteSISHCollectionResponse()->getCollection();
        $dossierArrete = $dossierArreteCollection[0];
        $esaboraManager = $this->provideEsaboraManagerForIntervention(self::UPDATE_ACTION);
        $esaboraManager->createOrUpdateArrete($this->getAffectation(PartnerType::ARS), $dossierArrete);
    }

    private function provideEsaboraManagerForIntervention(string $action): EsaboraManager
    {
        $this->interventionRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(self::CREATE_ACTION === $action ? null : new Intervention());

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
            $this->eventDispatcher,
            $this->userManager,
            $this->logger,
            $this->parameterBag,
        );
    }
}
