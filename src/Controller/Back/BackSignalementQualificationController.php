<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\QualificationNDERequest;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Manager\SignalementManager;
use App\Security\Voter\SignalementVoter;
use App\Service\Signalement\SignalementQualificationNde;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/bo/signalements')]
class BackSignalementQualificationController extends AbstractController
{
    #[Route(
        '/{uuid:signalement}/qualification/{signalementQualification}/editer',
        name: 'back_signalement_qualification_editer',
        methods: 'POST'
    )]
    public function editQualification(
        Request $request,
        Signalement $signalement,
        SignalementQualification $signalementQualification,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        SignalementQualificationNde $signalementQualificationNdeService,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(SignalementVoter::SIGN_EDIT_NDE, $signalement);
        $decodedRequest = json_decode($request->getContent());
        if ($this->isCsrfTokenValid('signalement_edit_nde_'.$signalement->getId(), $decodedRequest->_token)) {
            $qualificationNDERequest = $serializer->deserialize(
                $request->getContent(),
                QualificationNDERequest::class,
                'json'
            );
            $signalementManager->updateFromSignalementQualification(
                $signalementQualification,
                $qualificationNDERequest
            );
        } else {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => 'Le jeton CSRF est invalide. Veuillez actualiser la page et réessayer.'];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => false]);
        }

        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les modifications ont bien été enregistrées.'];
        [$signalementQualificationNDE, $signalementQualificationNDECriticites] = $signalementQualificationNdeService->getSignalementQualificationNdeAndCriticites($signalement);
        $nde = $this->renderView('back/signalement/view/nde.html.twig', [
            'signalement' => $signalement,
            'canEditNDE' => $this->isGranted('SIGN_EDIT_NDE', $signalement), // TODO : delete after rebase
            'signalementQualificationNDE' => $signalementQualificationNDE,
            'signalementQualificationNDECriticite' => $signalementQualificationNDECriticites,
        ]);
        $htmlTargetContents = [['target' => '#signalement-bo-nde-container', 'content' => $nde]];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
    }
}
