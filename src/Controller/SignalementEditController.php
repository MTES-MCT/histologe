<?php

namespace App\Controller;

use App\Entity\Model\InformationComplementaire;
use App\Entity\Model\InformationProcedure;
use App\Entity\Model\TypeCompositionLogement;
use App\Entity\Signalement;
use App\Form\SignalementeEditFO\CoordonneesAgenceType;
use App\Form\SignalementeEditFO\CoordonneesBailleurType;
use App\Form\SignalementeEditFO\InformationsGeneralesType;
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

    #[Route('/{code}/completer/informations-generales', name: 'front_suivi_signalement_complete_informations_generales', methods: ['GET', 'POST'])]
    public function suiviSignalementCompleteInformationsGenerales(
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

        $form = $this->createForm(InformationsGeneralesType::class, $signalement);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $typeCompositionLogement = $signalement->getTypeCompositionLogement() ? clone $signalement->getTypeCompositionLogement() : new TypeCompositionLogement();

            $typeCompositionLogement->setCompositionLogementNombreEnfants($form->get('nbEnfantsDansLogement')->getData())
                ->setCompositionLogementEnfants($form->get('enfantsDansLogementMoinsSixAns')->getData())
                ->setBailDpeBail($form->get('bail')->getData())
                ->setBailDpeEtatDesLieux($form->get('etatDesLieux')->getData())
                ->setBailDpeDpe($form->get('dpe')->getData())
                ->setBailDpeClasseEnergetique($form->get('classeEnergetique')->getData());
            $signalement->setTypeCompositionLogement($typeCompositionLogement);

            $informationComplementaire = $signalement->getInformationComplementaire() ? clone $signalement->getInformationComplementaire() : new InformationComplementaire();
            $dateEffetBail = $form->get('dateEffetBail')->getData() ? $form->get('dateEffetBail')->getData()->format('Y-m-d') : null;
            $informationComplementaire->setInformationsComplementairesSituationBailleurDateEffetBail($dateEffetBail)
                ->setInformationsComplementairesSituationOccupantsLoyersPayes($form->get('payementLoyersAJour')->getData())
                ->setInformationsComplementairesLogementAnneeConstruction($form->get('anneeConstruction')->getData());
            $signalement->setInformationComplementaire($informationComplementaire);

            $this->saveChangesAndCreateSuivi($signalement, $signalementUser);
            $this->addFlash('success', ['title' => 'Dossier complété', 'message' => 'Les informations générales ont bien été mises à jour.']);

            return $this->redirectToRoute('front_suivi_signalement_dossier', ['code' => $signalement->getCodeSuivi()]);
        }

        return $this->render('front/edit-signalement/informations-generales.html.twig', [
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
