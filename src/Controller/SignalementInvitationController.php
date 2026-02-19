<?php

namespace App\Controller;

use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Repository\SignalementRepository;
use App\Repository\TiersInvitationRepository;
use App\Security\Voter\SignalementFoVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/invitation')]
class SignalementInvitationController extends AbstractController
{
    #[Route('/{code}/accepter/{token}', name: 'front_suivi_invitation_accepter', methods: ['GET', 'POST'])]
    public function accepterInvitation(
        string $code,
        string $token,
        SignalementRepository $signalementRepository,
        TiersInvitationRepository $tiersInvitationRepository,
        SignalementManager $signalementManager,
        EntityManagerInterface $entityManager,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted(SignalementFoVoter::SIGN_ANSWER_INVITATION, $signalement);

        $tiersInvitation = $tiersInvitationRepository->findOneBy([
            'signalement' => $signalement,
            'token' => $token,
        ]);
        if (!$tiersInvitation) {
            throw $this->createNotFoundException('Invitation non trouvée');
        }

        $signalementManager->updateFromTiersInvitation($tiersInvitation);

        $entityManager->remove($tiersInvitation);
        $entityManager->flush();
        $this->addFlash('success', ['title' => 'Invitation acceptée', 'message' => 'Vous pouvez désormais suivre ce dossier.']);

        return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
    }

    #[Route('/{code}/refuser/{token}', name: 'front_suivi_invitation_refuser', methods: ['GET', 'POST'])]
    public function refuserInvitation(
        string $code,
        string $token,
        SignalementRepository $signalementRepository,
        TiersInvitationRepository $tiersInvitationRepository,
        SuiviManager $suiviManager,
        EntityManagerInterface $entityManager,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted(SignalementFoVoter::SIGN_ANSWER_INVITATION, $signalement);

        $tiersInvitation = $tiersInvitationRepository->findOneBy([
            'signalement' => $signalement,
            'token' => $token,
        ]);
        if (!$tiersInvitation) {
            throw $this->createNotFoundException('Invitation non trouvée');
        }

        $suiviManager->addRefuseInvitationSuivi($signalement);
        $entityManager->remove($tiersInvitation);
        $entityManager->flush();
        $this->addFlash('success', ['title' => 'Invitation refusée', 'message' => 'Vous avez refusé l\'invitation pour suivre ce dossier.']);

        return $this->redirectToRoute('home');
    }
}
