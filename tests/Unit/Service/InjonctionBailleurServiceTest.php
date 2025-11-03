<?php

namespace App\Tests\Unit\Service;

use App\Dto\StopProcedure;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Manager\AffectationManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Service\InjonctionBailleurService;
use App\Service\NotificationAndMailSender;
use App\Service\Signalement\AutoAssigner;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InjonctionBailleurServiceTest extends TestCase
{
    private MockObject&SuiviManager $suiviManager;
    private MockObject&NotificationAndMailSender $notificationAndMailSender;
    private MockObject&AutoAssigner $autoAssigner;
    private MockObject&EntityManagerInterface $entityManager;
    private MockObject&AffectationManager $affectationManager;
    private MockObject&UserManager $userManager;
    private InjonctionBailleurService $service;

    protected function setUp(): void
    {
        $this->suiviManager = $this->createMock(SuiviManager::class);
        $this->notificationAndMailSender = $this->createMock(NotificationAndMailSender::class);
        $this->autoAssigner = $this->createMock(AutoAssigner::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->affectationManager = $this->createMock(AffectationManager::class);
        $this->userManager = $this->createMock(UserManager::class);

        $this->service = new InjonctionBailleurService(
            $this->suiviManager,
            $this->notificationAndMailSender,
            $this->autoAssigner,
            $this->entityManager,
            $this->affectationManager,
            $this->userManager,
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

        $this->entityManager->expects($this->once())->method('flush');
        $this->notificationAndMailSender->expects($this->once())->method('sendNewSignalement')->with($signalement);
        $this->autoAssigner->expects($this->once())->method('assign')->with($signalement);

        $this->service->handleStopProcedure($stopProcedure);

        $this->assertSame(SignalementStatus::NEED_VALIDATION, $signalement->getStatut());
    }
}
