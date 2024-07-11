<?php

namespace App\Tests\Unit\Service;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Partner;
use App\Entity\Signalement;
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
        ->method('save');
        $this->testHelper($signalement, 1);
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
        ->method('save');
        $this->testHelper($signalement, 0);
        $this->assertEquals(Signalement::STATUS_NEED_VALIDATION, $signalement->getStatut());
    }

    public function testAutoAssignmentRuleArchived(): void
    {
        // l'autoAffectationRule de Loire-Atlantique est archivée, signalement 2023-27 en Loire-Atlantique
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2023-27']);
        $signalement->setStatut(Signalement::STATUS_NEED_VALIDATION);
        $this->suiviFactory->expects($this->never())
        ->method('createInstanceFrom');
        $this->suiviManager->expects($this->never())
        ->method('save');
        $this->testHelper($signalement, 0);
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
        ->method('save');
        $this->testHelper($signalement, 4);
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
        ->method('save');
        $this->testHelper($signalement, 0);
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
        ->method('save');
        $this->testHelper($signalement, 3);
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
        ->method('save');
        $this->testHelper($signalement, 3);
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
        ->method('save');
        $this->testHelper($signalement, 3);
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
        ->method('save');
        $this->testHelper($signalement, 2);
        $this->assertEquals(Signalement::STATUS_ACTIVE, $signalement->getStatut());
    }

    private function testHelper($signalement, $expectedCount)
    {
        $signalementManager = $this->createMock(SignalementManager::class);
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $userManager = $this->createMock(UserManager::class);
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $esaboraBus = $this->createMock(InterconnectionBus::class);
        $autoAssigner = new AutoAssigner(
            $signalementManager,
            $this->affectationManager,
            $this->suiviManager,
            $this->suiviFactory,
            $partnerRepository,
            $userManager,
            $parameterBag,
            $esaboraBus,
        );

        $autoAssigner->assign($signalement);
        $this->assertEquals($expectedCount, $autoAssigner->getCountAffectations());
    }
}
