<?php

namespace App\Tests\Unit\Service;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\Qualification;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Factory\SuiviFactory;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Messenger\InterconnectionBus;
use App\Repository\SignalementRepository;
use App\Service\Signalement\AutoAssigner;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AutoAssignerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private AffectationManager $affectationManager;
    private SignalementRepository $signalementRepository;
    private SuiviManager|MockObject $suiviManager;
    private SuiviFactory|MockObject $suiviFactory;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->affectationManager = self::getContainer()->get(AffectationManager::class);
        $this->signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $this->suiviManager = $this->createMock(SuiviManager::class);
        $this->suiviFactory = $this->createMock(SuiviFactory::class);
    }

    public function testAutoAssignmentSeineStDenis(): void
    {
        // Signalement '2024-05' en Seine-St-Denis, avec une règle d'affectation de commune
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-05']);
        $signalement->setStatut(Signalement::STATUS_NEED_VALIDATION);
        $this->suiviFactory->expects($this->once())
        ->method('createInstanceFrom');
        $this->suiviManager->expects($this->once())
        ->method('persist');
        $this->testHelper($signalement, 1, ['Mairie de Saint-Denis']);
        $this->assertEquals(Signalement::STATUS_ACTIVE, $signalement->getStatut());
    }

    public function testAutoAssignmentGexWithoutAutoAffectationRule(): void
    {
        // Signalement '2023-1' à Gex, sans règles d'affectation
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2023-1']);
        $signalement->setStatut(Signalement::STATUS_NEED_VALIDATION);
        $this->suiviFactory->expects($this->never())
        ->method('createInstanceFrom');
        $this->suiviManager->expects($this->never())
        ->method('persist');
        $this->testHelper($signalement, 0, []);
        $this->assertEquals(Signalement::STATUS_NEED_VALIDATION, $signalement->getStatut());
    }

    public function testAutoAssignmentLunel(): void
    {
        // Signalement '2024-06' à Lunel, correspond de base aux 4 règles d'auto-affectation de l'Hérault
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-06']);
        $signalement->setStatut(Signalement::STATUS_NEED_VALIDATION);
        $this->suiviFactory->expects($this->once())
        ->method('createInstanceFrom');
        $this->suiviManager->expects($this->once())
        ->method('persist');
        $this->testHelper($signalement, 4, ['Ville de Lunel', 'CAF 34', 'CD 34', 'CA Lunel Agglomération']);
        $this->assertEquals(Signalement::STATUS_ACTIVE, $signalement->getStatut());
    }

    public function testAutoAssignmentLunelIsLogementSocial(): void
    {
        // Les 4 règles de l'Hérault n'agissent que sur le parc privé
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-06']);
        $signalement->setStatut(Signalement::STATUS_NEED_VALIDATION);
        $signalement->setIsLogementSocial(true);
        $this->suiviFactory->expects($this->never())
        ->method('createInstanceFrom');
        $this->suiviManager->expects($this->never())
        ->method('persist');
        $this->testHelper($signalement, 0, []);
        $this->assertEquals(Signalement::STATUS_NEED_VALIDATION, $signalement->getStatut());
    }

    public function testAutoAssignmentLunelWithoutCAF(): void
    {
        // La règle de la CAF ne s'applique qu'aux signalements de la CAF
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-06']);
        $signalement->setStatut(Signalement::STATUS_NEED_VALIDATION);
        $signalement->setIsAllocataire('non');
        $this->suiviFactory->expects($this->once())
        ->method('createInstanceFrom');
        $this->suiviManager->expects($this->once())
        ->method('persist');
        $this->testHelper($signalement, 3, ['Ville de Lunel', 'CD 34', 'CA Lunel Agglomération']);
        $this->assertEquals(Signalement::STATUS_ACTIVE, $signalement->getStatut());
    }

    public function testAutoAssignmentLunelProfilBailleur(): void
    {
        // la règle de l'EPCI ne s'applique qu'au profil locataire
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-06']);
        $signalement->setStatut(Signalement::STATUS_NEED_VALIDATION);
        $signalement->setProfileDeclarant(ProfileDeclarant::BAILLEUR);
        $this->suiviFactory->expects($this->once())
        ->method('createInstanceFrom');
        $this->suiviManager->expects($this->once())
        ->method('persist');
        $this->testHelper($signalement, 3, ['Ville de Lunel', 'CAF 34', 'CD 34']);
        $this->assertEquals(Signalement::STATUS_ACTIVE, $signalement->getStatut());
    }

    public function testAutoAssignmentWithCodeInseeExcluded(): void
    {
        // la règle de l'EPCI exclue le code insee 34048
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-06']);
        $signalement->setStatut(Signalement::STATUS_NEED_VALIDATION);
        $signalement->setInseeOccupant('34048');
        $this->suiviFactory->expects($this->once())
        ->method('createInstanceFrom');
        $this->suiviManager->expects($this->once())
        ->method('persist');
        $this->testHelper($signalement, 3, ['Commune de Campagne', 'CAF 34', 'CD 34']);
        $this->assertEquals(Signalement::STATUS_ACTIVE, $signalement->getStatut());
    }

    public function testAutoAssignmentMontpellier(): void
    {
        // Montpellier n'est pas dans l'EPCI de Lunel, et pas dans la liste de code insee du conseil départemental
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-06']);
        $signalement->setStatut(Signalement::STATUS_NEED_VALIDATION);
        $signalement->setInseeOccupant('34172');
        $this->suiviFactory->expects($this->once())
        ->method('createInstanceFrom');
        $this->suiviManager->expects($this->once())
        ->method('persist');
        $this->testHelper($signalement, 2, ['Ville de Montpellier', 'CAF 34']);
        $this->assertEquals(Signalement::STATUS_ACTIVE, $signalement->getStatut());
    }

    public function testAutoAssignmentRuleArchived(): void
    {
        // l'autoAffectationRule des Bouches-du-Rhône est archivée, signalement 2022-1 en Loire-Atlantique
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2022-1']);
        $signalement->setStatut(Signalement::STATUS_NEED_VALIDATION);
        $this->suiviFactory->expects($this->never())
        ->method('createInstanceFrom');
        $this->suiviManager->expects($this->never())
        ->method('persist');
        $this->testHelper($signalement, 0, []);
        $this->assertEquals(Signalement::STATUS_NEED_VALIDATION, $signalement->getStatut());
    }

    public function testAutoAssignmentOneZoneIncludedOneCodeInsee(): void
    {
        // signalement 2023-27 au bourg de St-Mars, 1 partenaire au code insee, et 1 partenaire dans la zone
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2023-27']);
        $signalement->setStatut(Signalement::STATUS_NEED_VALIDATION);
        $this->suiviFactory->expects($this->once())
        ->method('createInstanceFrom');
        $this->suiviManager->expects($this->once())
        ->method('persist');
        $this->testHelper($signalement, 2, ['Mairie de Saint-Mars du Désert', 'Tiers-Lieu']);
        $this->assertEquals(Signalement::STATUS_ACTIVE, $signalement->getStatut());
    }

    public function testAutoAssignmentZoneIncluded(): void
    {
        // signalement 2024-09 à La Bodinière, 2 partenaires sur cette zone +1 en code insee
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-09']);
        $signalement->setStatut(Signalement::STATUS_NEED_VALIDATION);
        $this->suiviFactory->expects($this->once())
        ->method('createInstanceFrom');
        $this->suiviManager->expects($this->once())
        ->method('persist');
        $this->testHelper($signalement, 3, ['Mairie de Saint-Mars du Désert', 'Cocoland', 'Tiers-Lieu']);
        $this->assertEquals(Signalement::STATUS_ACTIVE, $signalement->getStatut());
    }

    public function testAutoAssignmentWithoutZoneWithoutInsee(): void
    {
        // signalement 2025-01 au Cellier, 1 partenaire pour le bailleur social, pas de partenaire sur le code insee ou la zone
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2025-01']);
        $signalement->setStatut(Signalement::STATUS_NEED_VALIDATION);
        $this->suiviFactory->expects($this->once())
        ->method('createInstanceFrom');
        $this->suiviManager->expects($this->once())
        ->method('persist');
        $this->testHelper($signalement, 1, ['Partner Habitat 44']);
        $this->assertEquals(Signalement::STATUS_ACTIVE, $signalement->getStatut());
    }

    public function testAutoAssignmentProcedure(): void
    {
        // signalement 2024-09 à La Bodinière, 2 partenaires sur cette zone +1 en code insee
        // + 1 partenaire si procédure DANGER
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-09']);
        $signalement->setStatut(Signalement::STATUS_NEED_VALIDATION);
        $signalement->addSignalementQualification((new SignalementQualification())->setQualification(
            Qualification::DANGER
        ));
        $this->suiviFactory->expects($this->once())
        ->method('createInstanceFrom');
        $this->suiviManager->expects($this->once())
        ->method('persist');
        $this->testHelper($signalement, 4, ['Mairie de Saint-Mars du Désert', 'SDIS 44', 'Cocoland', 'Tiers-Lieu']);
        $this->assertEquals(Signalement::STATUS_ACTIVE, $signalement->getStatut());
    }

    public function testAutoAssignmentBailleurSocial(): void
    {
        // signalement 2024-11 à Saint-Mars du Désert, logement social lié au bailleur Habitat 44
        // partenaire commune et partenaire bailleur social à affecter + partenaire dans la zone
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-11']);
        $signalement->setStatut(Signalement::STATUS_NEED_VALIDATION);
        $this->suiviFactory->expects($this->once())
        ->method('createInstanceFrom');
        $this->suiviManager->expects($this->once())
        ->method('persist');
        $this->testHelper($signalement, 3, ['Mairie de Saint-Mars du Désert', 'Partner Habitat 44', 'Tiers-Lieu']);
        $this->assertEquals(Signalement::STATUS_ACTIVE, $signalement->getStatut());
    }

    private function testHelper(Signalement $signalement, int $expectedCount, ?array $expectedPartnerNames = null)
    {
        /** @var SignalementManager|MockObject $signalementManager */
        $signalementManager = $this->createMock(SignalementManager::class);
        /** @var UserManager|MockObject $userManager */
        $userManager = $this->createMock(UserManager::class);
        /** @var ParameterBagInterface|MockObject $parameterBag */
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        /** @var InterconnectionBus|MockObject $esaboraBus */
        $esaboraBus = $this->createMock(InterconnectionBus::class);
        /** @var LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $autoAssigner = new AutoAssigner(
            $signalementManager,
            $this->affectationManager,
            $this->suiviManager,
            $this->suiviFactory,
            $userManager,
            $parameterBag,
            $esaboraBus,
            $logger,
        );

        $autoAssigner->assign($signalement);
        $this->assertEquals($expectedCount, $autoAssigner->getCountAffectations());
        if ($expectedPartnerNames) {
            sort($expectedPartnerNames);
            $partnerNames = $autoAssigner->getAffectedPartnerNames();
            sort($partnerNames);
            $this->assertEquals(\count($expectedPartnerNames), \count($partnerNames));
            $this->assertEquals($expectedPartnerNames, $partnerNames);
        }
    }
}
