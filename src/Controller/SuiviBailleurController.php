<?php

namespace App\Controller;

use App\Dto\ReponseInjonctionBailleur;
use App\Form\ReponseInjonctionBailleurType;
use App\Repository\SignalementRepository;
use App\Security\User\SignalementBailleur;
use App\Service\ReponseInjectionBailleurManager;
use App\Service\Signalement\SignalementDesordresProcessor;
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
        SignalementDesordresProcessor $signalementDesordresProcessor,
        ReponseInjectionBailleurManager $reponseInjectionBailleurManager,
    ): Response {
        /**
         * @var SignalementBailleur $user
         */
        $user = $this->getUser();
        $signalement = $signalementRepository->findOneBy(['uuid' => $user->getUserIdentifier()]);
        $infoDesordres = $signalementDesordresProcessor->process($signalement);
        $dateLimit = $signalement->getCreatedAt()->modify('+3 weeks -1 day');

        $reponseInjonctionBailleur = new ReponseInjonctionBailleur();
        $reponseInjonctionBailleur->setSignalement($signalement);
        $form = $this->createForm(ReponseInjonctionBailleurType::class, $reponseInjonctionBailleur, [
            'action' => $this->generateUrl('front_dossier_bailleur').'#form_reponse_injonction_bailleur_title',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $reponseInjectionBailleurManager->handleResponse($reponseInjonctionBailleur);
            $this->addFlash('success', 'Votre réponse a été enregistrée avec succès.');

            return $this->redirectToRoute('front_dossier_bailleur');
        }

        return $this->render('front/dossier_bailleur.html.twig', [
            'signalement' => $signalement,
            'infoDesordres' => $infoDesordres,
            'dateLimit' => $dateLimit,
            'form' => $form,
        ]);
    }
}
