<?php

namespace App\Controller;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Form\CoordonneesAgenceType;
use App\Form\CoordonneesBailleurType;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\SignalementRepository;
use App\Security\User\SignalementUser;
use App\Security\Voter\SignalementFoVoter;
use App\Service\Security\CguTiersChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/suivre-mon-signalement')]
class SignalementEditController extends AbstractController
{
    public function __construct(
        private readonly CguTiersChecker $cguTiersChecker,
        private readonly SuiviManager $suiviManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/{code}/completer/bailleur', name: 'front_suivi_signalement_complete_bailleur', methods: ['GET', 'POST'])]
    public function suiviSignalementCompleteBailleur(
        string $code,
        SignalementRepository $signalementRepository,
        Request $request,
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
            $this->saveChangesAndCreateSuivi($signalement, $signalementUser, 'coordonnees_bailleur');
            $this->addFlash('success', ['title' => 'Dossier complété', 'message' => 'Les coordonnées du bailleur ont bien été mises à jour.']);

            return $this->redirectToRoute('front_suivi_signalement_dossier', ['code' => $signalement->getCodeSuivi()]);
        }

        return $this->render('front/edit-signalement/coordonnees-bailleur.html.twig', [
            'signalement' => $signalement,
            'formCoordonneesBailleur' => $formCoordonneesBailleur,
        ]);
    }

    #[Route('/{code}/completer/agence', name: 'front_suivi_signalement_complete_agence', methods: ['GET', 'POST'])]
    public function suiviSignalementCompleteAgence(
        string $code,
        SignalementRepository $signalementRepository,
        Request $request,
    ): Response {
        $signalement = $signalementRepository->findOneByCodeForPublic($code);
        $this->denyAccessUnlessGranted(SignalementFoVoter::SIGN_USAGER_COMPLETE, $signalement);

        /** @var SignalementUser $signalementUser */
        $signalementUser = $this->getUser();

        if ($redirect = $this->cguTiersChecker->redirectIfTiersNeedsToAcceptCgu($signalement, $signalementUser->getEmail())) {
            return $redirect;
        }

        $form = $this->createForm(CoordonneesAgenceType::class, $signalement);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->saveChangesAndCreateSuivi($signalement, $signalementUser, 'coordonnees_agence');
            $this->addFlash('success', ['title' => 'Dossier complété', 'message' => 'Les coordonnées de l\'agence ont bien été mises à jour.']);

            return $this->redirectToRoute('front_suivi_signalement_dossier', ['code' => $signalement->getCodeSuivi()]);
        }

        return $this->render('front/edit-signalement/coordonnees-agence.html.twig', [
            'signalement' => $signalement,
            'form' => $form,
        ]);
    }

    private function saveChangesAndCreateSuivi(Signalement $signalement, SignalementUser $signalementUser, string $type): void
    {
        $uow = $this->entityManager->getUnitOfWork();
        $uow->computeChangeSets();
        $changeSet = $uow->getEntityChangeSet($signalement);
        if (empty($changeSet)) {
            return;
        }
        $listOfChanges = '';
        $whatsUpdated = '';
        switch ($type) {
            case 'coordonnees_bailleur':
                $whatsUpdated = 'les coordonnées du bailleur';
                $listOfChanges .= $signalement->getNomProprio() ? '<li>Nom : '.$signalement->getNomProprio().'</li>' : '';
                $listOfChanges .= $signalement->getPrenomProprio() ? '<li>Prénom : '.$signalement->getPrenomProprio().'</li>' : '';
                $listOfChanges .= $signalement->getMailProprio() ? '<li>E-mail : '.$signalement->getMailProprio().'</li>' : '';
                $listOfChanges .= $signalement->getTelProprio() ? '<li>Téléphone : '.$signalement->getTelProprio().'</li>' : '';
                $listOfChanges .= $signalement->getTelProprioSecondaire() ? '<li>Téléphone secondaire : '.$signalement->getTelProprioSecondaire().'</li>' : '';
                $listOfChanges .= $signalement->getAdresseProprio() ? '<li>Adresse : '.$signalement->getAdresseProprio().'</li>' : '';
                $listOfChanges .= $signalement->getCodePostalProprio() ? '<li>Code postal : '.$signalement->getCodePostalProprio().'</li>' : '';
                $listOfChanges .= $signalement->getVilleProprio() ? '<li>Ville : '.$signalement->getVilleProprio().'</li>' : '';
                break;
            case 'coordonnees_agence':
                $whatsUpdated = 'les coordonnées de l\'agence';
                $listOfChanges .= $signalement->getDenominationAgence() ? '<li>Dénomination : '.$signalement->getDenominationAgence().'</li>' : '';
                $listOfChanges .= $signalement->getNomAgence() ? '<li>Nom : '.$signalement->getNomAgence().'</li>' : '';
                $listOfChanges .= $signalement->getPrenomAgence() ? '<li>Prénom : '.$signalement->getPrenomAgence().'</li>' : '';
                $listOfChanges .= $signalement->getMailAgence() ? '<li>E-mail : '.$signalement->getMailAgence().'</li>' : '';
                $listOfChanges .= $signalement->getTelAgence() ? '<li>Téléphone : '.$signalement->getTelAgence().'</li>' : '';
                $listOfChanges .= $signalement->getTelAgenceSecondaire() ? '<li>Téléphone secondaire : '.$signalement->getTelAgenceSecondaire().'</li>' : '';
                $listOfChanges .= $signalement->getAdresseAgence() ? '<li>Adresse : '.$signalement->getAdresseAgence().'</li>' : '';
                $listOfChanges .= $signalement->getCodePostalAgence() ? '<li>Code postal : '.$signalement->getCodePostalAgence().'</li>' : '';
                $listOfChanges .= $signalement->getVilleAgence() ? '<li>Ville : '.$signalement->getVilleAgence().'</li>' : '';
                break;
        }
        /** @var User $user */
        $user = $signalementUser->getUser();
        $usager = ($user === $signalement->getSignalementUsager()?->getOccupant()) ? ' ('.UserManager::OCCUPANT.')' : ' ('.UserManager::DECLARANT.')';
        $description = $user->getNomComplet(true).$usager.' a mis à jour '.$whatsUpdated.'.';
        $description .= '<br>Voici les nouvelles coordonnées :';
        $description .= '<ul>';
        $description .= $listOfChanges;
        $description .= '</ul>';

        $this->suiviManager->createSuivi(
            signalement: $signalement,
            description: $description,
            type: Suivi::TYPE_USAGER,
            category: SuiviCategory::SIGNALEMENT_EDITED_FO,
            user: $user,
            isPublic: true,
        );
    }
}
