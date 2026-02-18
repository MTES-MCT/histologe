<?php

namespace App\Controller;

use App\Entity\Model\InformationProcedure;
use App\Entity\Model\SituationFoyer;
use App\Entity\Signalement;
use App\Form\SignalementeEditFO\CoordonneesAgenceType;
use App\Form\SignalementeEditFO\CoordonneesBailleurType;
use App\Form\SignalementeEditFO\ProcedureAssuranceType;
use App\Manager\SuiviManager;
use App\Repository\SignalementRepository;
use App\Security\User\SignalementUser;
use App\Security\Voter\SignalementFoVoter;
use App\Service\Security\CguTiersChecker;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
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
            $this->saveChangesAndCreateSuivi($signalement, $signalementUser);

            $this->addFlash('success', ['title' => 'Dossier complété', 'message' => 'Les coordonnées du bailleur ont bien été mises à jour.']);

            return $this->redirectToRoute('front_suivi_signalement_dossier', ['code' => $signalement->getCodeSuivi()]);
        }

        return $this->render('front/edit-signalement/coordonnees-bailleur.html.twig', [
            'signalement' => $signalement,
            'formCoordonneesBailleur' => $formCoordonneesBailleur,
        ]);
    }

    /**
     * @throws NonUniqueResultException
     */
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
            $this->saveChangesAndCreateSuivi($signalement, $signalementUser);
            $this->addFlash('success', ['title' => 'Dossier complété', 'message' => 'Les coordonnées de l\'agence ont bien été mises à jour.']);

            return $this->redirectToRoute('front_suivi_signalement_dossier', ['code' => $signalement->getCodeSuivi()]);
        }

        return $this->render('front/edit-signalement/coordonnees-agence.html.twig', [
            'signalement' => $signalement,
            'form' => $form,
        ]);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/{code}/completer/situation-foyer', name: 'front_suivi_signalement_complete_situation_foyer', methods: ['GET', 'POST'])]
    public function suiviSignalementCompleteSituationFoyer(
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
        
        $form = $this->createForm(UsagerSituationFoyerType::class, $signalement);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $situationFoyer = $signalement->getSituationFoyer() ? clone $signalement->getSituationFoyer() : new SituationFoyer();
            if ('nsp' === $form->get('isLogementSocial')->getData()) {
                $signalement->setIsLogementSocial(null);
                $situationFoyer->setLogementSocialAllocation(null);
            } elseif ($form->get('isLogementSocial')->getData()) {
                $situationFoyer->setLogementSocialAllocation('oui');
            } else {
                $situationFoyer->setLogementSocialAllocation('non');
            }
            $signalement->setSituationFoyer($situationFoyer);


            $this->saveChangesAndCreateSuivi($signalement, $signalementUser);
            $this->addFlash('success', ['title' => 'Dossier complété', 'message' => 'La situation du foyer a bien été mise à jour.']);
            return $this->redirectToRoute('front_suivi_signalement_dossier', ['code' => $signalement->getCodeSuivi()]);
        }
        
        return $this->render('front/edit-signalement/situation-foyer.html.twig', [
            'signalement' => $signalement,
            'form' => $form,
        ]);
    }

    #[Route('/{code}/completer/assurance', name: 'front_suivi_signalement_complete_assurance', methods: ['GET', 'POST'])]
    public function suiviSignalementCompleteAssurance(
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

        $informationProcedure = $signalement->getInformationProcedure() ? clone $signalement->getInformationProcedure() : new InformationProcedure();
        $form = $this->createForm(ProcedureAssuranceType::class, $informationProcedure);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $signalement->setInformationProcedure($informationProcedure);
            $this->saveChangesAndCreateSuivi($signalement, $signalementUser);
            $this->addFlash('success', ['title' => 'Dossier complété', 'message' => 'Les informations sur l\'assurance ont bien été mises à jour.']);

            return $this->redirectToRoute('front_suivi_signalement_dossier', ['code' => $signalement->getCodeSuivi()]);
        }

        return $this->render('front/edit-signalement/procedure-assurance.html.twig', [
            'signalement' => $signalement,
            'form' => $form,
        ]);
    }

    private function saveChangesAndCreateSuivi(Signalement $signalement, SignalementUser $signalementUser): void
    {
        // Ordre volontaire : createSuiviFromEditUsager() utilise les changements enregistrés sur Signalement en preUpdate.
        $this->entityManager->wrapInTransaction(function () use ($signalement, $signalementUser): void {
            $this->entityManager->flush(); /* @see SignalementUpdatedListener::preUpdate() écoute l'event dispatché par le flush() */
            $this->suiviManager->createSuiviFromEditUsager(
                $signalement,
                $signalementUser,
            );
        });
    }
}
