<?php

namespace App\Controller\Back;

use App\Entity\Signalement;
use App\Manager\SignalementManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/signalements')]
class SignalementEditController extends AbstractController
{
    #[Route('/{uuid}/edit-address', name: 'back_signalement_edit_address', methods: 'POST')]
    public function validationResponseSignalement(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
    ): Response {
        $this->denyAccessUnlessGranted('SIGN_VALIDATE', $signalement);
        if ($this->isCsrfTokenValid('signalement_edit_address_'.$signalement->getId(), $request->get('_token'))) {
            $signalement->setAdresseOccupant($request->get('edit-address-address-hidden'));
            $signalement->setCpOccupant($request->get('edit-address-code-postal-hidden'));
            $signalement->setVilleOccupant($request->get('edit-address-commune-hidden'));
            $signalement->setEtageOccupant($request->get('edit-address-etage'));
            $signalement->setEscalierOccupant($request->get('edit-address-escalier'));
            $signalement->setNumAppartOccupant($request->get('edit-address-num-appartement'));
            $signalement->setAdresseAutreOccupant($request->get('edit-address-autre'));

            $signalement->setGeoloc([
                'lat' => $request->get('edit-address-geoloc-lat-hidden'),
                'lng' => $request->get('edit-address-geoloc-lng-hidden'),
            ]);
            $signalement->setInseeOccupant($request->get('edit-address-insee-hidden'));

            $signalementManager->save($signalement);

            $this->addFlash('success', 'Adresse du logement mise à jour avec succés !');
        } else {
            $this->addFlash('error', 'Une erreur est survenue...');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }
}
