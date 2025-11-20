<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\AdresseOccupantRequest;
use App\Dto\Request\Signalement\CompositionLogementRequest;
use App\Dto\Request\Signalement\CoordonneesAgenceRequest;
use App\Dto\Request\Signalement\CoordonneesBailleurRequest;
use App\Dto\Request\Signalement\CoordonneesFoyerRequest;
use App\Dto\Request\Signalement\CoordonneesTiersRequest;
use App\Dto\Request\Signalement\InformationsLogementRequest;
use App\Dto\Request\Signalement\ProcedureDemarchesRequest;
use App\Dto\Request\Signalement\SituationFoyerRequest;
use App\Entity\Signalement;
use App\Entity\User;
use App\Manager\SignalementManager;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\FormHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/bo/signalements')]
class SignalementEditController extends AbstractController
{
    private const string ERROR_MSG = 'Une erreur s\'est produite. Veuillez actualiser la page.';

    #[Route('/{uuid:signalement}/edit-address', name: 'back_signalement_edit_address', methods: 'POST')]
    #[IsGranted('SIGN_EDIT', subject: 'signalement')]
    public function editAddress(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): JsonResponse {
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if ($this->isCsrfTokenValid('signalement_edit_address_'.$signalement->getId(), $token)) {
            /** @var AdresseOccupantRequest $adresseOccupantRequest */
            $adresseOccupantRequest = $serializer->deserialize(
                json_encode($payload),
                AdresseOccupantRequest::class,
                'json'
            );

            $errorMessage = FormHelper::getErrorsFromRequest($validator, $adresseOccupantRequest);
            if (empty($errorMessage)) {
                $subscriptionCreated = $signalementManager->updateFromAdresseOccupantRequest($signalement, $adresseOccupantRequest);
                $response = ['code' => Response::HTTP_OK];
                $this->addFlash('success', 'L\'adresse du logement a bien été modifiée.');
                if ($subscriptionCreated) {
                    $this->addFlash('success', User::MSG_SUBSCRIPTION_CREATED);
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

    #[Route('/{uuid:signalement}/edit-coordonnees-tiers', name: 'back_signalement_edit_coordonnees_tiers', methods: 'POST')]
    #[IsGranted('SIGN_EDIT', subject: 'signalement')]
    public function editCoordonneesTiers(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): JsonResponse {
        if ($signalement->isV2() && !$signalement->getIsNotOccupant()) {
            throw $this->createAccessDeniedException();
        }
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if ($this->isCsrfTokenValid(
            'signalement_edit_coordonnees_tiers_'.$signalement->getId(),
            $token
        )) {
            /** @var CoordonneesTiersRequest $coordonneesTiersRequest */
            $coordonneesTiersRequest = $serializer->deserialize(
                json_encode($request->getPayload()->all()),
                CoordonneesTiersRequest::class,
                'json'
            );

            $errorMessage = FormHelper::getErrorsFromRequest($validator, $coordonneesTiersRequest);

            if (empty($errorMessage)) {
                $subscriptionCreated = $signalementManager->updateFromCoordonneesTiersRequest($signalement, $coordonneesTiersRequest);
                $response = ['code' => Response::HTTP_OK];
                $this->addFlash('success', 'Les coordonnées du tiers déclarant ont bien été modifiées.');
                if ($subscriptionCreated) {
                    $this->addFlash('success', User::MSG_SUBSCRIPTION_CREATED);
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
    #[IsGranted('SIGN_EDIT', subject: 'signalement')]
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
        if ($this->isCsrfTokenValid(
            'signalement_edit_coordonnees_foyer_'.$signalement->getId(),
            $token
        )) {
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

            if (empty($errorMessage)) {
                $subscriptionCreated = $signalementManager->updateFromCoordonneesFoyerRequest($signalement, $coordonneesFoyerRequest);
                $response = ['code' => Response::HTTP_OK];
                $this->addFlash('success', 'Les coordonnées du foyer ont bien été modifiées.');
                if ($subscriptionCreated) {
                    $this->addFlash('success', User::MSG_SUBSCRIPTION_CREATED);
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

    #[Route('/{uuid:signalement}/edit-coordonnees-bailleur', name: 'back_signalement_edit_coordonnees_bailleur', methods: 'POST')]
    #[IsGranted('SIGN_EDIT', subject: 'signalement')]
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
        if ($this->isCsrfTokenValid(
            'signalement_edit_coordonnees_bailleur_'.$signalement->getId(),
            $token
        )) {
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

            if (empty($errorMessage)) {
                $subscriptionCreated = $signalementManager->updateFromCoordonneesBailleurRequest($signalement, $coordonneesBailleurRequest);
                $response = ['code' => Response::HTTP_OK];
                $this->addFlash('success', 'Les coordonnées du bailleur ont bien été modifiées.');
                if ($subscriptionCreated) {
                    $this->addFlash('success', User::MSG_SUBSCRIPTION_CREATED);
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

    #[Route('/{uuid:signalement}/edit-coordonnees-agence', name: 'back_signalement_edit_coordonnees_agence', methods: 'POST')]
    #[IsGranted('SIGN_EDIT', subject: 'signalement')]
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
        if ($this->isCsrfTokenValid(
            'signalement_edit_coordonnees_agence_'.$signalement->getId(),
            $token
        )) {
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

            if (empty($errorMessage)) {
                $subscriptionCreated = $signalementManager->updateFromCoordonneesAgenceRequest($signalement, $coordonneesAgenceRequest);
                $response = ['code' => Response::HTTP_OK];
                $this->addFlash('success', 'Les coordonnées de l\'agence ont bien été modifiées.');
                if ($subscriptionCreated) {
                    $this->addFlash('success', User::MSG_SUBSCRIPTION_CREATED);
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

    #[Route('/{uuid:signalement}/edit-informations-logement', name: 'back_signalement_edit_informations_logement', methods: 'POST')]
    #[IsGranted('SIGN_EDIT', subject: 'signalement')]
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
        if ($this->isCsrfTokenValid(
            'signalement_edit_informations_logement_'.$signalement->getId(),
            $token
        )) {
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

            if (empty($errorMessage)) {
                $subscriptionCreated = $signalementManager->updateFromInformationsLogementRequest($signalement, $informationsLogementRequest);
                $response = ['code' => Response::HTTP_OK];
                $this->addFlash('success', 'Les informations du logement ont bien été modifiées.');
                if ($subscriptionCreated) {
                    $this->addFlash('success', User::MSG_SUBSCRIPTION_CREATED);
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

    #[Route('/{uuid:signalement}/edit-composition-logement', name: 'back_signalement_edit_composition_logement', methods: 'POST')]
    #[IsGranted('SIGN_EDIT', subject: 'signalement')]
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
        if ($this->isCsrfTokenValid(
            'signalement_edit_composition_logement_'.$signalement->getId(),
            $token
        )) {
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
            if (empty($errorMessage)) {
                $subscriptionCreated = $signalementManager->updateFromCompositionLogementRequest($signalement, $compositionLogementRequest);
                $response = ['code' => Response::HTTP_OK];
                $this->addFlash('success', 'La description du logement a bien été modifiée.');
                if ($subscriptionCreated) {
                    $this->addFlash('success', User::MSG_SUBSCRIPTION_CREATED);
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

    /**
     * @throws \DateMalformedStringException
     */
    #[Route('/{uuid:signalement}/edit-situation-foyer', name: 'back_signalement_edit_situation_foyer', methods: 'POST')]
    #[IsGranted('SIGN_EDIT', subject: 'signalement')]
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
        if ($this->isCsrfTokenValid(
            'signalement_edit_situation_foyer_'.$signalement->getId(),
            $token
        )) {
            /** @var SituationFoyerRequest $situationFoyerRequest */
            $situationFoyerRequest = $serializer->deserialize(
                json_encode($request->getPayload()->all()),
                SituationFoyerRequest::class,
                'json'
            );

            $validationGroups = ['Default'];
            $validationGroups[] = $signalement->isV2() ? $signalement->getProfileDeclarant()->value : 'EDIT_'.$signalement->getProfileDeclarant()->value;

            $errorMessage = FormHelper::getErrorsFromRequest($validator, $situationFoyerRequest, $validationGroups);

            if (empty($errorMessage)) {
                $subscriptionCreated = $signalementManager->updateFromSituationFoyerRequest($signalement, $situationFoyerRequest);
                $response = ['code' => Response::HTTP_OK];
                $this->addFlash('success', 'La situation du foyer a bien été modifiée.');
                if ($subscriptionCreated) {
                    $this->addFlash('success', User::MSG_SUBSCRIPTION_CREATED);
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

    #[Route('/{uuid:signalement}/edit-procedure-demarches', name: 'back_signalement_edit_procedure_demarches', methods: 'POST')]
    #[IsGranted('SIGN_EDIT', subject: 'signalement')]
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
        if ($this->isCsrfTokenValid(
            'signalement_edit_procedure_demarches_'.$signalement->getId(),
            $token
        )) {
            /** @var ProcedureDemarchesRequest $procedureDemarchesRequest */
            $procedureDemarchesRequest = $serializer->deserialize(
                json_encode($request->getPayload()->all()),
                ProcedureDemarchesRequest::class,
                'json'
            );
            $validationGroups = ['Default'];
            $validationGroups[] = $signalement->isV2() ? $signalement->getProfileDeclarant()->value : 'EDIT_'.$signalement->getProfileDeclarant()->value;

            $errorMessage = FormHelper::getErrorsFromRequest($validator, $procedureDemarchesRequest, $validationGroups);

            if (empty($errorMessage)) {
                $subscriptionCreated = $signalementManager->updateFromProcedureDemarchesRequest($signalement, $procedureDemarchesRequest);
                $response = ['code' => Response::HTTP_OK];
                $this->addFlash('success', 'Les procédures et démarches ont bien été modifiées.');
                if ($subscriptionCreated) {
                    $this->addFlash('success', User::MSG_SUBSCRIPTION_CREATED);
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
}
