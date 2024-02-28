<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\AdresseOccupantRequest;
use App\Dto\Request\Signalement\CompositionLogementRequest;
use App\Dto\Request\Signalement\CoordonneesBailleurRequest;
use App\Dto\Request\Signalement\CoordonneesFoyerRequest;
use App\Dto\Request\Signalement\CoordonneesTiersRequest;
use App\Dto\Request\Signalement\InformationsLogementRequest;
use App\Dto\Request\Signalement\ProcedureDemarchesRequest;
use App\Dto\Request\Signalement\SituationFoyerRequest;
use App\Entity\Signalement;
use App\Manager\SignalementManager;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\FormHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/bo/signalements')]
class SignalementEditController extends AbstractController
{
    private const ERROR_MSG = 'Une erreur s\'est produite. Veuillez actualiser la page.';

    #[Route('/{uuid}/edit-address', name: 'back_signalement_edit_address', methods: 'POST')]
    public function editAddress(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_EDIT', $signalement);
        $payload = $request->getPayload()->all();
        if ($this->isCsrfTokenValid('signalement_edit_address_'.$signalement->getId(), $payload['_token'])) {
            /** @var AdresseOccupantRequest $adresseOccupantRequest */
            $adresseOccupantRequest = $serializer->deserialize(
                json_encode($payload),
                AdresseOccupantRequest::class,
                'json'
            );

            $errorMessage = FormHelper::getErrorsFromRequest($validator, $adresseOccupantRequest);
            if (empty($errorMessage)) {
                $signalementManager->updateFromAdresseOccupantRequest($signalement, $adresseOccupantRequest);
                $response = ['code' => Response::HTTP_OK];
                $this->addFlash('success', 'L\'adresse du logement a bien été modifiée.');
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

    #[Route('/{uuid}/edit-coordonnees-tiers', name: 'back_signalement_edit_coordonnees_tiers', methods: 'POST')]
    public function editCoordonneesTiers(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_EDIT', $signalement);
        $payload = $request->getPayload()->all();
        if ($this->isCsrfTokenValid(
            'signalement_edit_coordonnees_tiers_'.$signalement->getId(),
            $payload['_token']
        )) {
            /** @var CoordonneesTiersRequest $coordonneesTiersRequest */
            $coordonneesTiersRequest = $serializer->deserialize(
                json_encode($request->getPayload()->all()),
                CoordonneesTiersRequest::class,
                'json'
            );

            $errorMessage = FormHelper::getErrorsFromRequest($validator, $coordonneesTiersRequest);

            if (empty($errorMessage)) {
                $signalementManager->updateFromCoordonneesTiersRequest($signalement, $coordonneesTiersRequest);
                $response = ['code' => Response::HTTP_OK];
                $this->addFlash('success', 'Les coordonnées du tiers déclarant ont bien été modifiées.');
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

    #[Route('/{uuid}/edit-coordonnees-foyer', name: 'back_signalement_edit_coordonnees_foyer', methods: 'POST')]
    public function editCoordonneesFoyer(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('SIGN_EDIT', $signalement);
        $payload = $request->getPayload()->all();
        if ($this->isCsrfTokenValid(
            'signalement_edit_coordonnees_foyer_'.$signalement->getId(),
            $payload['_token']
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
                $signalementManager->updateFromCoordonneesFoyerRequest($signalement, $coordonneesFoyerRequest);
                $response = ['code' => Response::HTTP_OK];
                $this->addFlash('success', 'Les coordonnées du foyer ont bien été modifiées.');
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

    #[Route('/{uuid}/edit-coordonnees-bailleur', name: 'back_signalement_edit_coordonnees_bailleur', methods: 'POST')]
    public function editCoordonneesBailleur(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_EDIT', $signalement);
        $payload = $request->getPayload()->all();
        if ($this->isCsrfTokenValid(
            'signalement_edit_coordonnees_bailleur_'.$signalement->getId(),
            $payload['_token']
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
                $validator, $coordonneesBailleurRequest, $validationGroups
            );

            if (empty($errorMessage)) {
                $signalementManager->updateFromCoordonneesBailleurRequest($signalement, $coordonneesBailleurRequest);
                $response = ['code' => Response::HTTP_OK];
                $this->addFlash('success', 'Les coordonnées du bailleur ont bien été modifiées.');
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

    #[Route('/{uuid}/edit-informations-logement', name: 'back_signalement_edit_informations_logement', methods: 'POST')]
    public function editInformationsLogement(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_EDIT', $signalement);
        $payload = $request->getPayload()->all();
        if ($this->isCsrfTokenValid(
            'signalement_edit_informations_logement_'.$signalement->getId(),
            $payload['_token']
        )) {
            /** @var InformationsLogementRequest $informationsLogementRequest */
            $informationsLogementRequest = $serializer->deserialize(
                json_encode($request->getPayload()->all()),
                InformationsLogementRequest::class,
                'json'
            );

            $validationGroups = ['Default'];
            if ($signalement->getProfileDeclarant()) {
                $validationGroups[] = $signalement->getProfileDeclarant()->value;
            }
            $errorMessage = FormHelper::getErrorsFromRequest(
                $validator, $informationsLogementRequest, $validationGroups
            );

            if (empty($errorMessage)) {
                $signalementManager->updateFromInformationsLogementRequest($signalement, $informationsLogementRequest);
                $response = ['code' => Response::HTTP_OK];
                $this->addFlash('success', 'Les informations du logement ont bien été modifiées.');
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

    #[Route('/{uuid}/edit-composition-logement', name: 'back_signalement_edit_composition_logement', methods: 'POST')]
    public function editCompositionLogement(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SignalementDraftRequestSerializer $serializer,
        ValidatorInterface $validator,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_EDIT', $signalement);
        $payload = $request->getPayload()->all();
        if ($this->isCsrfTokenValid(
            'signalement_edit_composition_logement_'.$signalement->getId(),
            $payload['_token']
        )) {
            /** @var CompositionLogementRequest $compositionLogementRequest */
            $compositionLogementRequest = $serializer->deserialize(
                json_encode($request->getPayload()->all()),
                CompositionLogementRequest::class,
                'json'
            );

            $validationGroups = ['Default'];
            if ($signalement->getProfileDeclarant()) {
                $validationGroups[] = $signalement->getProfileDeclarant()->value;
            }

            $errorMessage = FormHelper::getErrorsFromRequest(
                $validator, $compositionLogementRequest, $validationGroups
            );
            if (empty($errorMessage)) {
                $signalementManager->updateFromCompositionLogementRequest($signalement, $compositionLogementRequest);
                $response = ['code' => Response::HTTP_OK];
                $this->addFlash('success', 'La composition du logement a bien été modifiée.');
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

    #[Route('/{uuid}/edit-situation-foyer', name: 'back_signalement_edit_situation_foyer', methods: 'POST')]
    public function editSituationFoyer(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_EDIT', $signalement);
        $payload = $request->getPayload()->all();
        if ($this->isCsrfTokenValid(
            'signalement_edit_situation_foyer_'.$signalement->getId(),
            $payload['_token']
        )) {
            /** @var SituationFoyerRequest $situationFoyerRequest */
            $situationFoyerRequest = $serializer->deserialize(
                json_encode($request->getPayload()->all()),
                SituationFoyerRequest::class,
                'json'
            );

            $validationGroups = ['Default'];
            if ($signalement->getProfileDeclarant()) {
                $validationGroups[] = $signalement->getProfileDeclarant()->value;
            }
            $errorMessage = FormHelper::getErrorsFromRequest($validator, $situationFoyerRequest, $validationGroups);

            if (empty($errorMessage)) {
                $signalementManager->updateFromSituationFoyerRequest($signalement, $situationFoyerRequest);
                $response = ['code' => Response::HTTP_OK];
                $this->addFlash('success', 'La situation du foyer a bien été modifiée.');
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

    #[Route('/{uuid}/edit-procedure-demarches', name: 'back_signalement_edit_procedure_demarches', methods: 'POST')]
    public function editProcedureDemarches(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_EDIT', $signalement);
        $payload = $request->getPayload()->all();
        if ($this->isCsrfTokenValid(
            'signalement_edit_procedure_demarches_'.$signalement->getId(),
            $payload['_token']
        )) {
            /** @var ProcedureDemarchesRequest $procedureDemarchesRequest */
            $procedureDemarchesRequest = $serializer->deserialize(
                json_encode($request->getPayload()->all()),
                ProcedureDemarchesRequest::class,
                'json'
            );
            $validationGroups = ['Default'];
            if ($signalement->getProfileDeclarant()) {
                $validationGroups[] = $signalement->getProfileDeclarant()->value;
            }
            $errorMessage = FormHelper::getErrorsFromRequest($validator, $procedureDemarchesRequest, $validationGroups);

            if (empty($errorMessage)) {
                $signalementManager->updateFromProcedureDemarchesRequest($signalement, $procedureDemarchesRequest);
                $response = ['code' => Response::HTTP_OK];
                $this->addFlash('success', 'Les procédures et démarches ont bien été modifiées.');
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
