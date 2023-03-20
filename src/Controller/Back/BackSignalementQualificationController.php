<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\QualificationNDERequest;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Manager\SignalementManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/bo/signalements')]
class BackSignalementQualificationController extends AbstractController
{
    #[Route(
        '/{uuid}/qualification/{signalement_qualification}/editer',
        name: 'back_signalement_qualification_editer',
        methods: 'POST'
    )]
    public function editQualification(
        Request $request,
        Signalement $signalement,
        SignalementQualification $signalementQualification,
        SignalementManager $signalementManager,
        SerializerInterface $serializer
    ): RedirectResponse|JsonResponse {
        $this->denyAccessUnlessGranted('SIGN_EDIT_NDE', $signalement);
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
            $this->addFlash('error', "Une erreur est survenu lors de l'édition");
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }
}
