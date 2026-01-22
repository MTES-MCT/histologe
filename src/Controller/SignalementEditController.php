<?php

namespace App\Controller;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Entity\User;
use App\Form\CoordonneesBailleurType;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\SignalementRepository;
use App\Security\User\SignalementUser;
use App\Security\Voter\SignalementFoVoter;
use App\Service\Security\CguTiersChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/suivre-mon-signalement')]
class SignalementEditController extends AbstractController
{
    public function __construct(
        private readonly CguTiersChecker $cguTiersChecker,
    ) {
    }

    #[Route('/{code}/completer/bailleur', name: 'front_suivi_signalement_complete_bailleur', methods: ['GET', 'POST'])]
    public function suiviSignalementCompleteBailleur(
        string $code,
        SignalementRepository $signalementRepository,
        Request $request,
        SuiviManager $suiviManager,
        SignalementManager $signalementManager,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted(SignalementFoVoter::SIGN_USAGER_COMPLETE, $signalement);

        /** @var SignalementUser $signalementUser */
        $signalementUser = $this->getUser();

        if ($redirect = $this->cguTiersChecker->redirectIfTiersNeedsToAcceptCgu($signalement, $signalementUser->getEmail())) {
            return $redirect;
        }

        $formCoordonneesBailleur = $this->createForm(
            CoordonneesBailleurType::class,
            $signalement,
            ['extended' => true]
        );
        $formCoordonneesBailleur->handleRequest($request);
        if (
            $formCoordonneesBailleur->isSubmitted()
            && $formCoordonneesBailleur->isValid()
        ) {
            /** @var User $user */
            $user = $signalementUser->getUser();
            $signalementManager->save($signalement);
            $usager = ($user === $signalement->getSignalementUsager()?->getOccupant()) ?
                ' ('.UserManager::OCCUPANT.')' :
                ' ('.UserManager::DECLARANT.')';
            $description = $user->getNomComplet(true).$usager.' a mis à jour les coordonnées du bailleur.';
            $description .= '<br>Voici les nouvelles coordonnées :';
            $description .= '<ul>';
            $description .= $signalement->getNomProprio() ? '<li>Nom : '.$signalement->getNomProprio().'</li>' : '';
            $description .= $signalement->getPrenomProprio() ? '<li>Prénom : '.$signalement->getPrenomProprio().'</li>' : '';
            $description .= $signalement->getMailProprio() ? '<li>E-mail : '.$signalement->getMailProprio().'</li>' : '';
            $description .= $signalement->getTelProprio() ? '<li>Téléphone : '.$signalement->getTelProprio().'</li>' : '';
            $description .= $signalement->getTelProprioSecondaire() ? '<li>Téléphone secondaire : '.$signalement->getTelProprioSecondaire().'</li>' : '';
            $description .= $signalement->getAdresseProprio() ? '<li>Adresse : '.$signalement->getAdresseProprio().'</li>' : '';
            $description .= $signalement->getCodePostalProprio() ? '<li>Code postal : '.$signalement->getCodePostalProprio().'</li>' : '';
            $description .= $signalement->getVilleProprio() ? '<li>Ville : '.$signalement->getVilleProprio().'</li>' : '';
            $description .= '</ul>';

            $suiviManager->createSuivi(
                signalement: $signalement,
                description: $description,
                type: Suivi::TYPE_USAGER,
                category: SuiviCategory::SIGNALEMENT_EDITED_FO,
                user: $user,
                isPublic: true,
            );

            $this->addFlash('success', ['title' => 'Dossier complété',
                'message' => 'Votre dossier a bien été complété, vous recevrez un e-mail lorsque votre dossier sera mis à jour. N\'hésitez pas à consulter votre page de suivi !',
            ]);

            return $this->redirectToRoute('front_suivi_signalement_dossier', ['code' => $signalement->getCodeSuivi()]);
        }

        return $this->render('front/edit-signalement/coordonnees-bailleur.html.twig', [
            'signalement' => $signalement,
            'formCoordonneesBailleur' => $formCoordonneesBailleur,
        ]);
    }
}
