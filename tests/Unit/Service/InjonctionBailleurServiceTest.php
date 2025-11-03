<?php

namespace App\Tests\Unit\Service;

use App\Dto\StopProcedure;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\PartnerRepository;
use App\Service\InjonctionBailleurService;
use App\Service\NotificationAndMailSender;
use App\Service\Signalement\AutoAssigner;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InjonctionBailleurServiceTest extends KernelTestCase
{
    private MockObject&SuiviManager $suiviManager;
    private MockObject&NotificationAndMailSender $notificationAndMailSender;
    private MockObject&AutoAssigner $autoAssigner;
    private EntityManagerInterface $entityManager;
    private AffectationManager $affectationManager;
    private UserManager $userManager;
    private SignalementManager $signalementManager;
    private PartnerRepository $partnerRepository;
    private InjonctionBailleurService $service;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->suiviManager = $this->createMock(SuiviManager::class);
        $this->notificationAndMailSender = $this->createMock(NotificationAndMailSender::class);
        $this->autoAssigner = $this->createMock(AutoAssigner::class);
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->affectationManager = self::getContainer()->get(AffectationManager::class);
        $this->userManager = self::getContainer()->get(UserManager::class);
        $this->signalementManager = self::getContainer()->get(SignalementManager::class);
        $this->partnerRepository = self::getContainer()->get(PartnerRepository::class);

        $this->service = new InjonctionBailleurService(
            $this->suiviManager,
            $this->notificationAndMailSender,
            $this->autoAssigner,
            $this->entityManager,
            $this->affectationManager,
            $this->userManager,
            $this->signalementManager,
            $this->partnerRepository,
        );
    }

    public function testHandleStopProcedure(): void
    {
        $signalement = new Signalement();
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

        $this->notificationAndMailSender->expects($this->once())->method('sendNewSignalement')->with($signalement);
        $this->autoAssigner->expects($this->once())->method('assign')->with($signalement);

        $this->service->handleStopProcedure($stopProcedure);

        $this->assertSame(SignalementStatus::NEED_VALIDATION, $signalement->getStatut());
    }

    public function testAssignHelpingPartners(): void
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2025-12']);

        $this->service->assignHelpingPartners($signalement);

        $this->assertCount(1, $signalement->getAffectations());
    }
}
