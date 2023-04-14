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
    private function getSecurityRedirect(Signalement $signalement, Request $request, string $tokenName): ?Response
    {
        // TODO : denyAccess ADD_INTERVENTION
        $this->denyAccessUnlessGranted('SIGN_VIEW', $signalement);
        if (Signalement::STATUS_ARCHIVED === $signalement->getStatut()) {
            $this->addFlash('error', "Ce signalement a été archivé et n'est pas consultable.");

            return $this->redirectToRoute('back_index');
        }

        if (!$this->isCsrfTokenValid($tokenName, $request->get('_token'))) {
            $this->addFlash('error', "Erreur de sécurisation de l'envoi de données.");

            return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
        }

        return null;
    }

    #[Route('/{uuid}/visites/ajouter', name: 'back_signalement_visite_add')]
    public function addVisiteToSignalement(
        Signalement $signalement,
        Request $request,
        InterventionManager $interventionManager,
    ): Response {
        $errorRedirect = $this->getSecurityRedirect(
            $signalement,
            $request,
            'signalement_add_visit_'.$signalement->getId()
        );
        if ($errorRedirect) {
            return $errorRedirect;
        }

        $requestData = $request->get('visite-add');
        $visiteRequest = new VisiteRequest(
            date: $requestData['date'],
            idPartner: $requestData['partner'],
        );

        if (!$interventionManager->createVisiteFromRequest($signalement, $visiteRequest)) {
            $this->addFlash('error', "Erreur lors de l'enregistrement de la visite.");
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid}/visites/annuler', name: 'back_signalement_visite_cancel')]
    public function cancelVisiteFromSignalement(
        Signalement $signalement,
        Request $request,
        InterventionManager $interventionManager,
    ): Response {
        $requestData = $request->get('visite-cancel');
        $errorRedirect = $this->getSecurityRedirect(
            $signalement,
            $request,
            'signalement_cancel_visit_'.$requestData['intervention']
        );
        if ($errorRedirect) {
            return $errorRedirect;
        }

        $visiteRequest = new VisiteRequest(
            idIntervention: $requestData['intervention'],
            details: $requestData['details'],
        );

        if (!$interventionManager->cancelVisiteFromRequest($signalement, $visiteRequest)) {
            $this->addFlash('error', "Erreur lors de l'annulation de la visite.");
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid}/visites/editer', name: 'back_signalement_visite_reschedule')]
    public function rescheduleVisiteFromSignalement(
        Signalement $signalement,
        Request $request,
        InterventionManager $interventionManager,
    ): Response {
        $requestData = $request->get('visite-reschedule');
        $errorRedirect = $this->getSecurityRedirect(
            $signalement,
            $request,
            'signalement_reschedule_visit_'.$requestData['intervention']
        );
        if ($errorRedirect) {
            return $errorRedirect;
        }

        $visiteRequest = new VisiteRequest(
            idIntervention: $requestData['intervention'],
            date: $requestData['date'],
            idPartner: $requestData['partner'],
        );

        if (!$interventionManager->rescheduleVisiteFromRequest($signalement, $visiteRequest)) {
            $this->addFlash('error', 'Erreur lors de la modification de la visite.');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid}/visites/confirmer', name: 'back_signalement_visite_confirm')]
    public function confirmVisiteFromSignalement(
        Signalement $signalement,
        Request $request,
        InterventionManager $interventionManager,
    ): Response {
        $requestData = $request->get('visite-confirm');
        $errorRedirect = $this->getSecurityRedirect(
            $signalement,
            $request,
            'signalement_confirm_visit_'.$requestData['intervention']
        );
        if ($errorRedirect) {
            return $errorRedirect;
        }

        $visiteRequest = new VisiteRequest(
            idIntervention: $requestData['intervention'],
            details: $requestData['details'],
            concludeProcedure: $requestData['concludeProcedure'],
            isVisiteDone: $requestData['visiteDone'],
            isOccupantPresent: $requestData['occupantPresent'],
        );

        if (!$interventionManager->confirmVisiteFromRequest($signalement, $visiteRequest)) {
            $this->addFlash('error', 'Erreur lors de la conclusion de la visite.');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }
}
