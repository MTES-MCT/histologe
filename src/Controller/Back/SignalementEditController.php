<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\AdresseOccupantRequest;
use App\Dto\Request\Signalement\CompositionLogementRequest;
use App\Dto\Request\Signalement\CoordonneesAgenceRequest;
use App\Dto\Request\Signalement\CoordonneesBailleurRequest;
use App\Dto\Request\Signalement\CoordonneesFoyerRequest;
use App\Dto\Request\Signalement\CoordonneesTiersRequest;
use App\Dto\Request\Signalement\InformationsLogementRequest;
use App\Dto\Request\Signalement\InviteTiersRequest;
use App\Dto\Request\Signalement\ProcedureDemarchesRequest;
use App\Dto\Request\Signalement\SituationFoyerRequest;
use App\Entity\Signalement;
use App\Entity\User;
use App\Manager\SignalementManager;
use App\Security\Voter\SignalementVoter;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\FormHelper;
use App\Service\HtmlTargetContentsService;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\MessageHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/bo/signalements')]
class SignalementEditController extends AbstractController
{
    private const string ERROR_MSG = 'Une erreur s\'est produite. Veuillez actualiser la page.';

    #[Route('/{uuid:signalement}/edit-address', name: 'back_signalement_edit_address', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editAddress(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        HtmlTargetContentsService $htmlTargetContentsService,
    ): JsonResponse {
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_address_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var AdresseOccupantRequest $adresseOccupantRequest */
        $adresseOccupantRequest = $serializer->deserialize(
            json_encode($payload),
            AdresseOccupantRequest::class,
            'json'
        );

        $errorMessage = FormHelper::getErrorsFromRequest($validator, $adresseOccupantRequest);
        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromAdresseOccupantRequest($signalement, $adresseOccupantRequest);
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'L\'adresse du logement a bien été modifiée.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = $htmlTargetContentsService->getHtmlTargetContentsForSignalementAddress($signalement);

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
    }

    #[Route('/{uuid:signalement}/edit-coordonnees-tiers', name: 'back_signalement_edit_coordonnees_tiers', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editCoordonneesTiers(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): JsonResponse {
        // On bloque si ça a été crééé par un occupant et qu'aucun mail n'a encore été renseigné (via invitation)
        if ($signalement->isV2() && !$signalement->getIsNotOccupant() && empty($signalement->getMailDeclarant())) {
            throw $this->createAccessDeniedException();
        }
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_coordonnees_tiers_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var CoordonneesTiersRequest $coordonneesTiersRequest */
        $coordonneesTiersRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            CoordonneesTiersRequest::class,
            'json'
        );

        $errorMessage = FormHelper::getErrorsFromRequest($validator, $coordonneesTiersRequest);

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromCoordonneesTiersRequest($signalement, $coordonneesTiersRequest);
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les coordonnées du tiers déclarant ont bien été modifiées.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = [
            [
                'target' => '#signalement-information-tiers-container',
                'content' => $this->renderView('back/signalement/view/information/information-tiers.html.twig', ['signalement' => $signalement]),
            ],
        ];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
    }

    #[Route('/{uuid:signalement}/edit-invite-tiers', name: 'back_signalement_edit_invite_tiers', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editInviteTiers(
        #[MapRequestPayload(validationGroups: ['false'])]
        InviteTiersRequest $inviteTiersRequest,
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        ValidatorInterface $validator,
        NotificationMailerRegistry $notificationMailerRegistry,
    ): JsonResponse {
        // On bloque si tiers déjà renseigné ou si créé par tiers
        if (!empty($signalement->getMailDeclarant()) || ($signalement->isV2() && $signalement->getIsNotOccupant())) {
            throw $this->createAccessDeniedException();
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if ($this->isCsrfTokenValid(
            'signalement_edit_invite_tiers_'.$signalement->getId(),
            $token
        )) {
            $errorMessage = FormHelper::getErrorsFromRequest($validator, $inviteTiersRequest);

            if (empty($errorMessage)) {
                $subscriptionCreated = $signalementManager->updateFromInviteTiersRequest($signalement, $inviteTiersRequest);

                $notificationMailerRegistry->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_INVITE_TIERS,
                        to: $signalement->getMailDeclarant(),
                        signalement: $signalement,
                    )
                );

                $response = ['code' => Response::HTTP_OK];
                $this->addFlash('success', ['title' => 'Invitation sur le dossier',
                    'message' => 'Le tiers aidant a bien été invité.',
                ]);
                if ($subscriptionCreated) {
                    $this->addFlash('success', ['title' => 'Abonnement au dossier',
                        'message' => User::MSG_SUBSCRIPTION_CREATED,
                    ]);
                }
            } else {
                $response = ['code' => Response::HTTP_BAD_REQUEST];
                $response = [...$response, ...$errorMessage];
            }
        } else {
            $response = [
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => self::ERROR_MSG,
            ];
        }

        return $this->json($response, $response['code']);
    }

    #[Route('/{uuid:signalement}/edit-coordonnees-foyer', name: 'back_signalement_edit_coordonnees_foyer', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editCoordonneesFoyer(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): JsonResponse {
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_coordonnees_foyer_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var CoordonneesFoyerRequest $coordonneesFoyerRequest */
        $coordonneesFoyerRequest = $serializer->deserialize(
            json_encode($payload),
            CoordonneesFoyerRequest::class,
            'json'
        );

        $validationGroups = ['Default'];
        if ($signalement->getProfileDeclarant()) {
            $validationGroups[] = $signalement->getProfileDeclarant()->value;
        }
        $errorMessage = FormHelper::getErrorsFromRequest($validator, $coordonneesFoyerRequest, $validationGroups);

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromCoordonneesFoyerRequest($signalement, $coordonneesFoyerRequest);
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les coordonnées du foyer ont bien été modifiées.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = [
            [
                'target' => '#signalement-title-container',
                'content' => $this->renderView('back/signalement/view/header/_title.html.twig', ['signalement' => $signalement]),
            ],
            [
                'target' => '#signalement-information-foyer-container',
                'content' => $this->renderView('back/signalement/view/information/information-foyer.html.twig', ['signalement' => $signalement]),
            ],
        ];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
    }

    #[Route('/{uuid:signalement}/edit-coordonnees-bailleur', name: 'back_signalement_edit_coordonnees_bailleur', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editCoordonneesBailleur(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): JsonResponse {
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_coordonnees_bailleur_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var CoordonneesBailleurRequest $coordonneesBailleurRequest */
        $coordonneesBailleurRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            CoordonneesBailleurRequest::class,
            'json'
        );
        $validationGroups = ['Default'];
        if ($signalement->getProfileDeclarant()) {
            $validationGroups[] = $signalement->getProfileDeclarant()->value;
        }
        $errorMessage = FormHelper::getErrorsFromRequest(
            $validator,
            $coordonneesBailleurRequest,
            $validationGroups
        );

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromCoordonneesBailleurRequest($signalement, $coordonneesBailleurRequest);
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les coordonnées du bailleur ont bien été modifiées.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = [
            [
                'target' => '#signalement-information-bailleur-container',
                'content' => $this->renderView('back/signalement/view/information/information-bailleur.html.twig', ['signalement' => $signalement]),
            ],
        ];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
    }

    #[Route('/{uuid:signalement}/edit-coordonnees-agence', name: 'back_signalement_edit_coordonnees_agence', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editCoordonneesAgence(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): JsonResponse {
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_coordonnees_agence_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }

        /** @var CoordonneesAgenceRequest $coordonneesAgenceRequest */
        $coordonneesAgenceRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            CoordonneesAgenceRequest::class,
            'json'
        );
        $validationGroups = ['Default'];
        if ($signalement->getProfileDeclarant()) {
            $validationGroups[] = $signalement->getProfileDeclarant()->value;
        }
        $errorMessage = FormHelper::getErrorsFromRequest(
            $validator,
            $coordonneesAgenceRequest,
            $validationGroups
        );

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromCoordonneesAgenceRequest($signalement, $coordonneesAgenceRequest);
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les coordonnées de l\'agence ont bien été modifiées.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = [
            [
                'target' => '#signalement-information-agence-container',
                'content' => $this->renderView('back/signalement/view/information/information-agence.html.twig', ['signalement' => $signalement]),
            ],
        ];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
    }

    #[Route('/{uuid:signalement}/edit-informations-logement', name: 'back_signalement_edit_informations_logement', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editInformationsLogement(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): JsonResponse {
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_informations_logement_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var InformationsLogementRequest $informationsLogementRequest */
        $informationsLogementRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            InformationsLogementRequest::class,
            'json'
        );
        $validationGroups = ['Default'];
        $validationGroups[] = $signalement->isV2() ? $signalement->getProfileDeclarant()->value : 'EDIT_'.$signalement->getProfileDeclarant()->value;

        $errorMessage = FormHelper::getErrorsFromRequest(
            $validator,
            $informationsLogementRequest,
            $validationGroups
        );

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromInformationsLogementRequest($signalement, $informationsLogementRequest);
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les informations du logement ont bien été modifiées.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = [
            [
                'target' => '#signalement-information-logement-container',
                'content' => $this->renderView('back/signalement/view/information/information-logement.html.twig', ['signalement' => $signalement]),
            ],
        ];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
    }

    #[Route('/{uuid:signalement}/edit-composition-logement', name: 'back_signalement_edit_composition_logement', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editCompositionLogement(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SignalementDraftRequestSerializer $serializer,
        ValidatorInterface $validator,
    ): JsonResponse {
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_composition_logement_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var CompositionLogementRequest $compositionLogementRequest */
        $compositionLogementRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            CompositionLogementRequest::class,
            'json'
        );

        $validationGroups = ['Default'];
        $validationGroups[] = $signalement->isV2() ? $signalement->getProfileDeclarant()->value : 'EDIT_'.$signalement->getProfileDeclarant()->value;

        $errorMessage = FormHelper::getErrorsFromRequest(
            $validator,
            $compositionLogementRequest,
            $validationGroups
        );
        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromCompositionLogementRequest($signalement, $compositionLogementRequest);
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'La description du logement a bien été modifiée.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = [
            [
                'target' => '#signalement-information-composition-container',
                'content' => $this->renderView('back/signalement/view/information/information-composition.html.twig', ['signalement' => $signalement]),
            ],
        ];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
    }

    /**
     * @throws \DateMalformedStringException
     */
    #[Route('/{uuid:signalement}/edit-situation-foyer', name: 'back_signalement_edit_situation_foyer', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editSituationFoyer(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): JsonResponse {
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_situation_foyer_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var SituationFoyerRequest $situationFoyerRequest */
        $situationFoyerRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            SituationFoyerRequest::class,
            'json'
        );

        $validationGroups = ['Default'];
        $validationGroups[] = $signalement->isV2() ? $signalement->getProfileDeclarant()->value : 'EDIT_'.$signalement->getProfileDeclarant()->value;

        $errorMessage = FormHelper::getErrorsFromRequest($validator, $situationFoyerRequest, $validationGroups);

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromSituationFoyerRequest($signalement, $situationFoyerRequest);
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'La situation du foyer a bien été modifiée.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = [
            [
                'target' => '#signalement-information-situation-foyer-container',
                'content' => $this->renderView('back/signalement/view/information/information-situation-foyer.html.twig', ['signalement' => $signalement]),
            ],
        ];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
    }

    #[Route('/{uuid:signalement}/edit-procedure-demarches', name: 'back_signalement_edit_procedure_demarches', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editProcedureDemarches(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): JsonResponse {
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_procedure_demarches_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var ProcedureDemarchesRequest $procedureDemarchesRequest */
        $procedureDemarchesRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            ProcedureDemarchesRequest::class,
            'json'
        );
        $validationGroups = ['Default'];
        $validationGroups[] = $signalement->isV2() ? $signalement->getProfileDeclarant()->value : 'EDIT_'.$signalement->getProfileDeclarant()->value;

        $errorMessage = FormHelper::getErrorsFromRequest($validator, $procedureDemarchesRequest, $validationGroups);

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromProcedureDemarchesRequest($signalement, $procedureDemarchesRequest);
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les procédures et démarches ont bien été modifiées.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = [
            [
                'target' => '#signalement-information-procedure-container',
                'content' => $this->renderView('back/signalement/view/information/information-procedure.html.twig', ['signalement' => $signalement]),
            ],
        ];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
    }
}
