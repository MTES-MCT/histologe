<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\AdresseOccupantRequest;
use App\Dto\Request\Signalement\CoordonneesBailleurRequest;
use App\Dto\Request\Signalement\CoordonneesFoyerRequest;
use App\Dto\Request\Signalement\CoordonneesTiersRequest;
use App\Dto\Request\Signalement\InformationsLogementRequest;
use App\Dto\Request\Signalement\ProcedureDemarchesRequest;
use App\Dto\Request\Signalement\SituationFoyerRequest;
use App\Entity\Signalement;
use App\Manager\SignalementManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/bo/signalements')]
class SignalementEditController extends AbstractController
{
    #[Route('/{uuid}/edit-address', name: 'back_signalement_edit_address', methods: 'POST')]
    public function editAddress(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_VALIDATE', $signalement);
        if ($this->isCsrfTokenValid('signalement_edit_address_'.$signalement->getId(), $request->get('_token'))) {
            /** @var AdresseOccupantRequest $adresseOccupantRequest */
            $adresseOccupantRequest = $serializer->deserialize(
                json_encode($request->getPayload()->all()),
                AdresseOccupantRequest::class,
                'json'
            );

            $errorMessage = $this->getErrorMessage($validator, $adresseOccupantRequest);

            if (empty($errorMessage)) {
                $signalementManager->updateFromAdresseOccupantRequest($signalement, $adresseOccupantRequest);
                $this->addFlash('success', 'Adresse du logement mise à jour avec succès !');
            } else {
                $this->addFlash('error', 'Erreur de saisie : '.$errorMessage);
            }
        } else {
            $this->addFlash('error', 'Une erreur est survenue...');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid}/edit-coordonnees-tiers', name: 'back_signalement_edit_coordonnees_tiers', methods: 'POST')]
    public function editCoordonneesTiers(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_VALIDATE', $signalement);
        if ($this->isCsrfTokenValid('signalement_edit_coordonnees_tiers_'.$signalement->getId(), $request->get('_token'))) {
            /** @var CoordonneesTiersRequest $coordonneesTiersRequest */
            $coordonneesTiersRequest = $serializer->deserialize(
                json_encode($request->getPayload()->all()),
                CoordonneesTiersRequest::class,
                'json'
            );

            $errorMessage = $this->getErrorMessage($validator, $coordonneesTiersRequest);

            if (empty($errorMessage)) {
                $signalementManager->updateFromCoordonneesTiersRequest($signalement, $coordonneesTiersRequest);
                $this->addFlash('success', 'Coordonnées du tiers déclarant mises à jour avec succès !');
            } else {
                $this->addFlash('error', 'Erreur de saisie : '.$errorMessage);
            }
        } else {
            $this->addFlash('error', 'Une erreur est survenue...');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid}/edit-coordonnees-foyer', name: 'back_signalement_edit_coordonnees_foyer', methods: 'POST')]
    public function editCoordonneesFoyer(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_VALIDATE', $signalement);
        if ($this->isCsrfTokenValid('signalement_edit_coordonnees_foyer_'.$signalement->getId(), $request->get('_token'))) {
            /** @var CoordonneesFoyerRequest $coordonneesFoyerRequest */
            $coordonneesFoyerRequest = $serializer->deserialize(
                json_encode($request->getPayload()->all()),
                CoordonneesFoyerRequest::class,
                'json'
            );

            $errorMessage = $this->getErrorMessage($validator, $coordonneesFoyerRequest);

            if (empty($errorMessage)) {
                $signalementManager->updateFromCoordonneesFoyerRequest($signalement, $coordonneesFoyerRequest);
                $this->addFlash('success', 'Coordonnées du foyer mises à jour avec succès !');
            } else {
                $this->addFlash('error', 'Erreur de saisie : '.$errorMessage);
            }
        } else {
            $this->addFlash('error', 'Une erreur est survenue...');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid}/edit-coordonnees-bailleur', name: 'back_signalement_edit_coordonnees_bailleur', methods: 'POST')]
    public function editCoordonneesBailleur(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_VALIDATE', $signalement);
        if ($this->isCsrfTokenValid('signalement_edit_coordonnees_bailleur_'.$signalement->getId(), $request->get('_token'))) {
            /** @var CoordonneesBailleurRequest $coordonneesBailleurRequest */
            $coordonneesBailleurRequest = $serializer->deserialize(
                json_encode($request->getPayload()->all()),
                CoordonneesBailleurRequest::class,
                'json'
            );

            $errorMessage = $this->getErrorMessage($validator, $coordonneesBailleurRequest);

            if (empty($errorMessage)) {
                $signalementManager->updateFromCoordonneesBailleurRequest($signalement, $coordonneesBailleurRequest);
                $this->addFlash('success', 'Coordonnées du bailleur mises à jour avec succès !');
            } else {
                $this->addFlash('error', 'Erreur de saisie : '.$errorMessage);
            }
        } else {
            $this->addFlash('error', 'Une erreur est survenue...');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid}/edit-informations-logement', name: 'back_signalement_edit_informations_logement', methods: 'POST')]
    public function editInformationsLogement(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_VALIDATE', $signalement);
        if ($this->isCsrfTokenValid('signalement_edit_informations_logement_'.$signalement->getId(), $request->get('_token'))) {
            /** @var InformationsLogementRequest $informationsLogementRequest */
            $informationsLogementRequest = $serializer->deserialize(
                json_encode($request->getPayload()->all()),
                InformationsLogementRequest::class,
                'json'
            );

            $errorMessage = $this->getErrorMessage($validator, $informationsLogementRequest);

            if (empty($errorMessage)) {
                $signalementManager->updateFromInformationsLogementRequest($signalement, $informationsLogementRequest);
                $this->addFlash('success', 'Informations du logement mises à jour avec succès !');
            } else {
                $this->addFlash('error', 'Erreur de saisie : '.$errorMessage);
            }
        } else {
            $this->addFlash('error', 'Une erreur est survenue...');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid}/edit-situation-foyer', name: 'back_signalement_edit_situation_foyer', methods: 'POST')]
    public function editSituationFoyer(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_VALIDATE', $signalement);
        if ($this->isCsrfTokenValid('signalement_edit_situation_foyer_'.$signalement->getId(), $request->get('_token'))) {
            /** @var SituationFoyerRequest $situationFoyerRequest */
            $situationFoyerRequest = $serializer->deserialize(
                json_encode($request->getPayload()->all()),
                SituationFoyerRequest::class,
                'json'
            );

            $errorMessage = $this->getErrorMessage($validator, $situationFoyerRequest);

            if (empty($errorMessage)) {
                $signalementManager->updateFromSituationFoyerRequest($signalement, $situationFoyerRequest);
                $this->addFlash('success', 'Situation du foyer mise à jour avec succès !');
            } else {
                $this->addFlash('error', 'Erreur de saisie : '.$errorMessage);
            }
        } else {
            $this->addFlash('error', 'Une erreur est survenue...');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid}/edit-procedure-demarches', name: 'back_signalement_edit_procedure_demarches', methods: 'POST')]
    public function editProcedureDemarches(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_VALIDATE', $signalement);
        if ($this->isCsrfTokenValid('signalement_edit_procedure_demarches_'.$signalement->getId(), $request->get('_token'))) {
            /** @var ProcedureDemarchesRequest $procedureDemarchesRequest */
            $procedureDemarchesRequest = $serializer->deserialize(
                json_encode($request->getPayload()->all()),
                ProcedureDemarchesRequest::class,
                'json'
            );

            $errorMessage = $this->getErrorMessage($validator, $procedureDemarchesRequest);

            if (empty($errorMessage)) {
                $signalementManager->updateFromProcedureDemarchesRequest($signalement, $procedureDemarchesRequest);
                $this->addFlash('success', 'Procédure et démarches mises à jour avec succès !');
            } else {
                $this->addFlash('error', 'Erreur de saisie : '.$errorMessage);
            }
        } else {
            $this->addFlash('error', 'Une erreur est survenue...');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    private function getErrorMessage(ValidatorInterface $validator, $dtoRequest): string
    {
        $errorMessage = '';
        $errors = $validator->validate($dtoRequest);
        if (\count($errors) > 0) {
            $errorMessage = '';
            foreach ($errors as $error) {
                $errorMessage .= $error->getMessage().' ';
            }
        }

        return $errorMessage;
    }
}
