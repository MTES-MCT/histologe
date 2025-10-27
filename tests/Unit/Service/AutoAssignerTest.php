<?php

namespace App\Tests\Unit\Service;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\UserManager;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Service\Signalement\AutoAssigner;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AutoAssignerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private SignalementManager $signalementManager;
    private AffectationManager $affectationManager;
    private SignalementRepository $signalementRepository;
    private PartnerRepository $partnerRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
        $this->signalementManager = self::getContainer()->get(SignalementManager::class);
        $this->affectationManager = self::getContainer()->get(AffectationManager::class);
        $this->signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $this->partnerRepository = $this->entityManager->getRepository(Partner::class);
    }

    public function testAutoAssignmentSeineStDenis(): void
    {
        // Signalement '2024-05' en Seine-St-Denis, avec une règle d'affectation de commune
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-05']);
        $signalement->setStatut(SignalementStatus::NEED_VALIDATION);
        $this->testHelper($signalement, 1, ['Mairie de Saint-Denis']);
        $this->assertEquals(SignalementStatus::ACTIVE, $signalement->getStatut());
        $this->assertcount(1, $signalement->getSuivis());
    }

    public function testAutoAssignmentGexWithoutAutoAffectationRule(): void
    {
        // Signalement '2023-1' à Gex, sans règles d'affectation
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2023-1']);
        $signalement->setStatut(SignalementStatus::NEED_VALIDATION);
        $this->testHelper($signalement, 0, []);
        $this->assertEquals(SignalementStatus::NEED_VALIDATION, $signalement->getStatut());
        $this->assertcount(1, $signalement->getSuivis());
    }

    public function testAutoAssignmentLunel(): void
    {
        // Signalement '2024-06' à Lunel, correspond de base aux 4 règles d'auto-affectation de l'Hérault
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-06']);
        $signalement->setStatut(SignalementStatus::NEED_VALIDATION);
        $this->testHelper($signalement, 4, ['Ville de Lunel', 'CAF 34', 'CD 34', 'CA Lunel Agglomération']);
        $this->assertEquals(SignalementStatus::ACTIVE, $signalement->getStatut());
        $this->assertcount(1, $signalement->getSuivis());
    }

    public function testAutoAssignmentLunelIsLogementSocial(): void
    {
        // Les 4 règles de l'Hérault n'agissent que sur le parc privé
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-06']);
        $signalement->setStatut(SignalementStatus::NEED_VALIDATION);
        $signalement->setIsLogementSocial(true);
        $this->testHelper($signalement, 0, []);
        $this->assertEquals(SignalementStatus::NEED_VALIDATION, $signalement->getStatut());
        $this->assertcount(0, $signalement->getSuivis());
    }

    public function testAutoAssignmentLunelWithoutCAF(): void
    {
        // La règle de la CAF ne s'applique qu'aux signalements de la CAF
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-06']);
        $signalement->setStatut(SignalementStatus::NEED_VALIDATION);
        $signalement->setIsAllocataire('non');
        $this->testHelper($signalement, 3, ['Ville de Lunel', 'CD 34', 'CA Lunel Agglomération']);
        $this->assertEquals(SignalementStatus::ACTIVE, $signalement->getStatut());
        $this->assertcount(1, $signalement->getSuivis());
    }

    public function testAutoAssignmentLunelProfilBailleur(): void
    {
        // la règle de l'EPCI ne s'applique qu'au profil locataire
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-06']);
        $signalement->setStatut(SignalementStatus::NEED_VALIDATION);
        $signalement->setProfileDeclarant(ProfileDeclarant::BAILLEUR);
        $this->testHelper($signalement, 3, ['Ville de Lunel', 'CAF 34', 'CD 34']);
        $this->assertEquals(SignalementStatus::ACTIVE, $signalement->getStatut());
        $this->assertcount(1, $signalement->getSuivis());
    }

    public function testAutoAssignmentWithCodeInseeExcluded(): void
    {
        // la règle de l'EPCI exclue le code insee 34048
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-06']);
        $signalement->setStatut(SignalementStatus::NEED_VALIDATION);
        $signalement->setInseeOccupant('34048');
        $this->testHelper($signalement, 3, ['Commune de Campagne', 'CAF 34', 'CD 34']);
        $this->assertEquals(SignalementStatus::ACTIVE, $signalement->getStatut());
        $this->assertcount(1, $signalement->getSuivis());
    }

    public function testAutoAssignmentMontpellier(): void
    {
        // Montpellier n'est pas dans l'EPCI de Lunel, et pas dans la liste de code insee du conseil départemental
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-06']);
        $signalement->setStatut(SignalementStatus::NEED_VALIDATION);
        $signalement->setInseeOccupant('34172');
        $this->testHelper($signalement, 2, ['Ville de Montpellier', 'CAF 34']);
        $this->assertEquals(SignalementStatus::ACTIVE, $signalement->getStatut());
        $this->assertcount(1, $signalement->getSuivis());
    }

    public function testAutoAssignmentRuleArchived(): void
    {
        // l'autoAffectationRule des Bouches-du-Rhône est archivée, signalement 2022-1 en Loire-Atlantique
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2022-1']);
        $signalement->setStatut(SignalementStatus::NEED_VALIDATION);
        $this->testHelper($signalement, 0, []);
        $this->assertEquals(SignalementStatus::NEED_VALIDATION, $signalement->getStatut());
        $this->assertcount(1, $signalement->getSuivis());
    }

    public function testAutoAssignmentOneZoneIncludedOneCodeInsee(): void
    {
        // signalement 2023-27 au bourg de St-Mars, 1 partenaire au code insee, et 1 partenaire dans la zone
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2023-27']);
        $signalement->setStatut(SignalementStatus::NEED_VALIDATION);
        $this->testHelper($signalement, 2, ['Mairie de Saint-Mars du Désert', 'Tiers-Lieu']);
        $this->assertEquals(SignalementStatus::ACTIVE, $signalement->getStatut());
        $this->assertcount(1, $signalement->getSuivis());
    }

    public function testAutoAssignmentZoneIncluded(): void
    {
        // signalement 2024-09 à La Bodinière, 2 partenaires sur cette zone +1 en code insee
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-09']);
        $signalement->setStatut(SignalementStatus::NEED_VALIDATION);
        $this->testHelper($signalement, 3, ['Mairie de Saint-Mars du Désert', 'Cocoland', 'Tiers-Lieu']);
        $this->assertEquals(SignalementStatus::ACTIVE, $signalement->getStatut());
        $this->assertcount(1, $signalement->getSuivis());
    }

    public function testAutoAssignmentWithoutZoneWithoutInsee(): void
    {
        // signalement 2025-01 au Cellier, pas de partenaire sur le code insee ou la zone
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2025-01']);
        $signalement->setStatut(SignalementStatus::NEED_VALIDATION);
        $this->testHelper($signalement, 0, null);
        $this->assertEquals(SignalementStatus::NEED_VALIDATION, $signalement->getStatut());
        $this->assertcount(0, $signalement->getSuivis());
    }

    public function testAutoAssignmentProcedure(): void
    {
        // signalement 2024-09 à La Bodinière, 2 partenaires sur cette zone +1 en code insee
        // + 1 partenaire si procédure DANGER
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-09']);
        $signalement->setStatut(SignalementStatus::NEED_VALIDATION);
        $signalement->addSignalementQualification((new SignalementQualification())->setQualification(
            Qualification::DANGER
        ));
        $this->testHelper($signalement, 4, ['Mairie de Saint-Mars du Désert', 'SDIS 44', 'Cocoland', 'Tiers-Lieu']);
        $this->assertEquals(SignalementStatus::ACTIVE, $signalement->getStatut());
        $this->assertcount(1, $signalement->getSuivis());
    }

    public function testAutoAssignmentBailleurSocial(): void
    {
        // signalement 2024-11 à Saint-Mars du Désert, logement social lié au bailleur Habitat 44
        // partenaire commune et partenaire bailleur social à affecter + partenaire dans la zone
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2024-11']);
        $signalement->setStatut(SignalementStatus::NEED_VALIDATION);
        $this->testHelper($signalement, 3, ['Mairie de Saint-Mars du Désert', 'Partner Habitat 44', 'Tiers-Lieu']);
        $this->assertEquals(SignalementStatus::ACTIVE, $signalement->getStatut());
        $this->assertcount(1, $signalement->getSuivis());
    }

    /**
     * @param ?array<string> $expectedPartnerNames
     */
    private function testHelper(Signalement $signalement, int $expectedCount, ?array $expectedPartnerNames = null): void
    {
        foreach ($signalement->getAffectations() as $affectation) {
            $signalement->removeAffectation($affectation);
        }

        /** @var UserManager&MockObject $userManager */
        $userManager = $this->createMock(UserManager::class);
        /** @var LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $autoAssigner = new AutoAssigner(
            $this->signalementManager,
            $this->affectationManager,
            $userManager,
            $this->partnerRepository,
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
