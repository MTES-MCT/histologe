<?php

namespace App\Controller;

use App\Dto\ReponseInjonctionBailleur;
use App\Dto\StopProcedure;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Form\CoordonneesBailleurType;
use App\Form\ReponseInjonctionBailleurType;
use App\Form\StopProcedureType;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Security\User\SignalementBailleur;
use App\Service\InjonctionBailleurService;
use App\Service\Signalement\SignalementDesordresProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dossier-bailleur')]
class SuiviBailleurController extends AbstractController
{
    #[Route('/', name: 'front_dossier_bailleur', methods: ['GET', 'POST'])]
    public function dossierBailleur(
        Request $request,
        SignalementRepository $signalementRepository,
        SuiviRepository $suiviRepository,
        SignalementDesordresProcessor $signalementDesordresProcessor,
        InjonctionBailleurService $injonctionBailleurService,
        EntityManagerInterface $entityManager,
    ): Response {
        /**
         * @var SignalementBailleur $user
         */
        $user = $this->getUser();
        $signalement = $signalementRepository->findOneBy(['uuid' => $user->getUserIdentifier()]);
        $infoDesordres = $signalementDesordresProcessor->process($signalement);
        $dateLimit = $signalement->getCreatedAt()->modify('+'.InjonctionBailleurService::DELAIS_DE_REPONSE.' -1 day');
        $suiviReponse = $suiviRepository->findOneBy(['signalement' => $signalement, 'category' => SuiviCategory::injonctionBailleurReponseCategories()]);
        $suiviBasculeProcedure = $suiviRepository->findOneBy(['signalement' => $signalement, 'category' => SuiviCategory::INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR]);

        $form = null;
        $formStopProcedure = null;
        if ($suiviReponse && SignalementStatus::INJONCTION_BAILLEUR === $signalement->getStatut()) {
            if (!$signalement->getMailProprio()) {
                $form = $this->createForm(CoordonneesBailleurType::class, $signalement, [
                    'action' => $this->generateUrl('front_dossier_bailleur').'#form_coordonnees_bailleur_title',
                ]);
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $entityManager->flush();
                    $this->addFlash('success', 'Vos coordonnées ont été enregistrées avec succès.');

                    return $this->redirectToRoute('front_dossier_bailleur');
                }
            }
            if (in_array($suiviReponse->getCategory(), [SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI, SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE], true)) {
                $stopProcedure = new StopProcedure();
                $stopProcedure->setSignalement($signalement);

                $formStopProcedure = $this->createForm(StopProcedureType::class, $stopProcedure, [
                    'action' => $this->generateUrl('front_dossier_bailleur').'#form_stop_procedure_bailleur_title',
                ]);
                $formStopProcedure->handleRequest($request);

                if ($formStopProcedure->isSubmitted() && $formStopProcedure->isValid()) {
                    $injonctionBailleurService->handleStopProcedure($stopProcedure);
                    $this->addFlash('success', 'Votre réponse a été enregistrée avec succès.');

                    return $this->redirectToRoute('front_dossier_bailleur');
                }
            }
        } elseif (SignalementStatus::INJONCTION_BAILLEUR === $signalement->getStatut()) {
            $reponseInjonctionBailleur = new ReponseInjonctionBailleur();
            $reponseInjonctionBailleur->setSignalement($signalement);
            $form = $this->createForm(ReponseInjonctionBailleurType::class, $reponseInjonctionBailleur, [
                'action' => $this->generateUrl('front_dossier_bailleur').'#form_reponse_injonction_bailleur_title',
            ]);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $injonctionBailleurService->handleResponse($reponseInjonctionBailleur);
                $this->addFlash('success', 'Votre réponse a été enregistrée avec succès.');

                return $this->redirectToRoute('front_dossier_bailleur');
            }
        }

        return $this->render('front/dossier_bailleur.html.twig', [
            'signalement' => $signalement,
            'infoDesordres' => $infoDesordres,
            'suiviReponse' => $suiviReponse,
            'suiviBasculeProcedure' => $suiviBasculeProcedure,
            'dateLimit' => $dateLimit,
            'form' => $form,
            'formStopProcedure' => $formStopProcedure,
        ]);
    }
}
