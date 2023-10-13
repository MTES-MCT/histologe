<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\AdresseOccupantRequest;
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
    public function validationResponseSignalement(
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

            $errorMessage = '';
            $errors = $validator->validate($adresseOccupantRequest);
            if (\count($errors) > 0) {
                $errorMessage = '';
                foreach ($errors as $error) {
                    $errorMessage .= $error->getMessage().' ';
                }
            }

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
}
