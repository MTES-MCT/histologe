<?php

namespace App\Controller;

use App\Entity\Signalement;
use App\Entity\TiersInvitation;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Security\Voter\SignalementFoVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/invitation')]
class SignalementInvitationController extends AbstractController
{
    #[Route('/{code}/accepter/{token}', name: 'front_suivi_invitation_accepter', methods: ['GET', 'POST'])]
    public function accepterInvitation(
        #[MapEntity(expr: 'repository.findOneByCodeForPublic(code)')]
        Signalement $signalement,
        #[MapEntity(expr: 'repository.findOneByCodeAndToken(code, token)')]
        TiersInvitation $tiersInvitation,
        SignalementManager $signalementManager,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted(SignalementFoVoter::SIGN_USAGER_EDIT_OFFLINE, $signalement);

        if ($tiersInvitation->isRefused()) {
            $this->addFlash('info', ['title' => 'Invitation déjà refusée', 'message' => 'Vous avez déjà refusé cette invitation.']);

            return $this->redirectToRoute('home');
        }
        if ($tiersInvitation->isWaiting()) {
            $signalementManager->updateFromTiersInvitation($tiersInvitation);
            $tiersInvitation->accept();
            $entityManager->flush();
            $this->addFlash('success', ['title' => 'Invitation acceptée', 'message' => 'Vous pouvez désormais suivre ce dossier.']);
        } elseif ($tiersInvitation->isAccepted()) {
            $this->addFlash('success', ['title' => 'Invitation déjà acceptée', 'message' => 'Vous pouvez vous connecter pour suivre ce dossier.']);
        }

        return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
    }

    #[Route('/{code}/refuser/{token}', name: 'front_suivi_invitation_refuser', methods: ['GET', 'POST'])]
    public function refuserInvitation(
        #[MapEntity(expr: 'repository.findOneByCodeForPublic(code)')]
        Signalement $signalement,
        #[MapEntity(expr: 'repository.findOneByCodeAndToken(code, token)')]
        TiersInvitation $tiersInvitation,
        SuiviManager $suiviManager,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted(SignalementFoVoter::SIGN_USAGER_EDIT_OFFLINE, $signalement);

        if ($tiersInvitation->isAccepted()) {
            $this->addFlash('success', ['title' => 'Invitation déjà acceptée', 'message' => 'Vous pouvez vous connecter pour suivre ce dossier.']);

            return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
        }
        if ($tiersInvitation->isWaiting()) {
            $suiviManager->addRefuseInvitationSuivi($signalement);
            $tiersInvitation->refuse();
            $entityManager->flush();
            $this->addFlash('success', ['title' => 'Invitation refusée', 'message' => 'Vous avez refusé l\'invitation pour suivre ce dossier.']);
        } elseif ($tiersInvitation->isRefused()) {
            $this->addFlash('info', ['title' => 'Invitation déjà refusée', 'message' => 'Vous avez déjà refusé cette invitation.']);
        }

        return $this->redirectToRoute('home');
    }
}
