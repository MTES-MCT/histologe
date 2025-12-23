<?php

namespace App\Tests\Unit\Service\InjonctionBailleur;

use App\Dto\StopProcedure;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Manager\AffectationManager;
use App\Manager\FileManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\PartnerRepository;
use App\Service\InjonctionBailleur\EngagementTravauxBailleurGenerator;
use App\Service\InjonctionBailleur\InjonctionBailleurService;
use App\Service\Signalement\AutoAssigner;
use App\Service\Signalement\ReferenceGenerator;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class InjonctionBailleurServiceTest extends KernelTestCase
{
    private MockObject&SuiviManager $suiviManager;
    private MockObject&AutoAssigner $autoAssigner;
    private EntityManagerInterface $entityManager;
    private AffectationManager $affectationManager;
    private UserManager $userManager;
    private SignalementManager $signalementManager;
    private PartnerRepository $partnerRepository;
    private ReferenceGenerator $referenceGenerator;
    private InjonctionBailleurService $service;
    private EngagementTravauxBailleurGenerator $engagementTravauxBailleurGenerator;
    private ParameterBagInterface $parameterBag;
    private UploadHandlerService $uploadHandlerService;
    private FileManager $fileManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->suiviManager = $this->createMock(SuiviManager::class);
        $this->autoAssigner = $this->createMock(AutoAssigner::class);
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();
        $this->entityManager = $entityManager;
        $this->affectationManager = self::getContainer()->get(AffectationManager::class);
        $this->userManager = self::getContainer()->get(UserManager::class);
        $this->signalementManager = self::getContainer()->get(SignalementManager::class);
        $this->partnerRepository = self::getContainer()->get(PartnerRepository::class);
        $this->referenceGenerator = self::getContainer()->get(ReferenceGenerator::class);
        $this->engagementTravauxBailleurGenerator = self::getContainer()->get(EngagementTravauxBailleurGenerator::class);
        $this->parameterBag = self::getContainer()->get(ParameterBagInterface::class);
        $this->uploadHandlerService = self::getContainer()->get(UploadHandlerService::class);
        $this->fileManager = self::getContainer()->get(FileManager::class);

        $this->service = new InjonctionBailleurService(
            $this->suiviManager,
            $this->autoAssigner,
            $this->entityManager,
            $this->affectationManager,
            $this->userManager,
            $this->signalementManager,
            $this->partnerRepository,
            $this->referenceGenerator,
            $this->engagementTravauxBailleurGenerator,
            $this->parameterBag,
            $this->uploadHandlerService,
            $this->fileManager,
        );
    }

    public function testHandleStopProcedure(): void
    {
        $signalement = new Signalement();
        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => '30']);
        $signalement->setTerritory($territory);
        $stopProcedure = new StopProcedure();
        $stopProcedure->setSignalement($signalement);
        $stopProcedure->setDescription('Le bailleur souhaite repasser en procédure classique.');

        // On s'attend à 2 appels à createSuivi
        $this->suiviManager->expects($this->exactly(2))
            ->method('createSuivi')
            ->withConsecutive(
                [
                    $this->callback(fn ($arg) => $arg instanceof Signalement),
                    $this->stringContains('arrêter la procédure d\'injonction'),
                    Suivi::TYPE_AUTO,
                    SuiviCategory::INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR,
                    $this->anything(), // on ignore les autres params facultatifs
                ],
                [
                    $this->callback(fn ($arg) => $arg instanceof Signalement),
                    'Le bailleur souhaite repasser en procédure classique.',
                    Suivi::TYPE_AUTO,
                    SuiviCategory::INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR_COMMENTAIRE,
                    $this->anything(),
                ]
            );

        $this->autoAssigner->expects($this->once())->method('assignOrSendNewSignalementNotification')->with($signalement);
        $this->entityManager->beginTransaction();
        $this->service->handleStopProcedure($stopProcedure);
        $this->entityManager->commit();

        $this->assertSame(SignalementStatus::NEED_VALIDATION, $signalement->getStatut());
        $this->assertStringStartsWith(date('Y').'-', $signalement->getReference());
        $this->assertStringNotContainsString('-TEMPORAIRE', $signalement->getReference());
    }

    public function testAssignHelpingPartners(): void
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2025-000000000012']);

        $this->service->assignHelpingPartners($signalement);

        $this->assertCount(1, $signalement->getAffectations());
    }
}
