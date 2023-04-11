<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\VisiteRequest;
use App\Entity\Signalement;
use App\Manager\InterventionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/signalements')]
class SignalementVisitesController extends AbstractController
{
    #[Route('/{uuid}/visites/ajouter', name: 'back_signalement_visite_add')]
    public function addVisiteToSignalement(
        Signalement $signalement,
        Request $request,
        InterventionManager $interventionManager,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_VIEW', $signalement);
        if (Signalement::STATUS_ARCHIVED === $signalement->getStatut()) {
            $this->addFlash('error', "Ce signalement a été archivé et n'est pas consultable.");

            return $this->redirectToRoute('back_index');
        }

        if (!$this->isCsrfTokenValid('signalement_add_visit_'.$signalement->getId(), $request->get('_token'))) {
            $this->addFlash('error', "Erreur de sécurisation de l'envoi de données.");

            return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
        }

        $requestData = $request->get('visite-add');
        $visiteRequest = new VisiteRequest(
            $requestData['date'],
            $requestData['partner'],
        );

        if (!$interventionManager->createVisiteFromRequest($signalement, $visiteRequest)) {
            $this->addFlash('error', "Erreur lors de l'enregistrement de la visite.");
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }
}
