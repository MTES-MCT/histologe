<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\QualificationNDERequest;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Manager\SignalementManager;
use App\Security\Voter\SignalementVoter;
use App\Service\MessageHelper;
use App\Service\Signalement\SignalementQualificationNde;
use App\Utils\FormHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

// TODO à la suppression de FEATURE_ORIENTATION : supprimer ce contrôleur (route remplacée par SignalementEditController::editConsommationEnergertique)
// ainsi que _panel-edit-nde.html.twig, nde.html.twig, form_nde.js, SignalementManager::updateFromSignalementQualification
// et les tests associés (BackSignalementQualificationControllerTest, SignalementManagerTest)
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
        ValidatorInterface $validator,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(SignalementVoter::SIGN_EDIT_NDE, $signalement);
        $decodedRequest = json_decode($request->getContent());
        if ($this->isCsrfTokenValid('signalement_edit_nde_'.$signalement->getId(), $decodedRequest->_token)) {
            $qualificationNDERequest = $serializer->deserialize(
                $request->getContent(),
                QualificationNDERequest::class,
                'json'
            );
            $errorMessage = FormHelper::getErrorsFromRequest($validator, $qualificationNDERequest);
            if (!empty($errorMessage)) {
                $response = ['code' => Response::HTTP_BAD_REQUEST];
                $response = [...$response, ...$errorMessage];

                return $this->json($response, $response['code']);
            }
            $signalementManager->updateFromSignalementQualification(
                $signalementQualification,
                $qualificationNDERequest
            );
        } else {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => false]);
        }

        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les modifications ont bien été enregistrées.'];
        [$signalementQualificationNDE, $signalementQualificationNDECriticites] = $signalementQualificationNdeService->getSignalementQualificationNdeAndCriticites($signalement);
        $nde = $this->renderView('back/signalement/view/nde.html.twig', [
            'signalement' => $signalement,
            'signalementQualificationNDE' => $signalementQualificationNDE,
            'signalementQualificationNDECriticite' => $signalementQualificationNDECriticites,
        ]);
        $htmlTargetContents = [
            ['target' => '#signalement-bo-nde-container', 'content' => $nde],
            [
                'target' => '#signalement-information-composition-container',
                'content' => $this->renderView('back/signalement/view/information/information-composition.html.twig', ['signalement' => $signalement]),
            ],
        ];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
    }
}
