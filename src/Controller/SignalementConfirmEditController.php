<?php

namespace App\Controller;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Manager\SuiviManager;
use App\Security\Voter\SignalementFoVoter;
use App\Service\Signalement\Suivi\SuiviDescriptionHelper;
use App\Service\Signalement\Suivi\SuiviDescriptionVariant;
use Doctrine\ORM\EntityManagerInterface;
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
        SuiviDescriptionHelper $suiviDescriptionHelper,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted(SignalementFoVoter::SIGN_USAGER_EDIT_OFFLINE, $signalement);
        if ($signalement->getMailOccupantTemp()) {
            $old = $signalement->getMailOccupant();
            $signalement->setMailOccupant($signalement->getMailOccupantTemp());
            $signalement->setMailOccupantTemp(null);

            $suiviManager->createSuivi(
                signalement: $signalement,
                description: $suiviDescriptionHelper->buildDescriptionForCreation(
                    SuiviCategory::SIGNALEMENT_EDITED_FO,
                    ['old' => $old, 'new' => $signalement->getMailOccupant()],
                    SuiviDescriptionVariant::MODIFICATION_EMAIL_OCCUPANT,
                ),
                category: SuiviCategory::SIGNALEMENT_EDITED_FO,
                isVisibleForUsager: true,
            );
            $entityManager->flush();
            $this->addFlash('success', 'L\'adresse e-mail de l\'occupant a été mise à jour avec succès.');
        }

        return $this->redirectToRoute('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()]);
    }
}
