<?php

namespace App\Controller;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Manager\SuiviManager;
use App\Security\Voter\SignalementFoVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/confirm-edit')]
class SignalementConfirmEditController extends AbstractController
{
    #[Route('/{code}/mail-occupant', name: 'front_signalement_confirm_edit_mail_occupant', methods: ['GET'], defaults: ['_signed' => true])]
    public function accepterInvitation(
        #[MapEntity(expr: 'repository.findOneByCodeForPublic(code)')]
        Signalement $signalement,
        SuiviManager $suiviManager,
    ): Response {
        $this->denyAccessUnlessGranted(SignalementFoVoter::SIGN_USAGER_EDIT_OFFLINE, $signalement);
        if ($signalement->getMailOccupantTemp()) {
            $old = $signalement->getMailOccupant();
            $signalement->setMailOccupant($signalement->getMailOccupantTemp());
            $signalement->setMailOccupantTemp(null);

            $suiviManager->createSuivi(
                signalement: $signalement,
                description: $this->renderView('suivi/front_signalement_confirm_edit_email_occupant.html.twig', ['old' => $old, 'new' => $signalement->getMailOccupant()]),
                type: Suivi::TYPE_USAGER,
                category: SuiviCategory::SIGNALEMENT_EDITED_FO,
                isPublic: true,
            );
            $this->addFlash('success', 'L\'adresse e-mail de l\'occupant a été mise à jour avec succès.');
        }

        return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
    }
}
