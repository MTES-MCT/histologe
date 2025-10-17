<?php

namespace App\Controller;

use App\Dto\ReponseInjonctionBailleur;
use App\Entity\Enum\SuiviCategory;
use App\Form\CoordonneesBailleurType;
use App\Form\ReponseInjonctionBailleurType;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Security\User\SignalementBailleur;
use App\Service\ReponseInjectionBailleurManager;
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
        ReponseInjectionBailleurManager $reponseInjectionBailleurManager,
        EntityManagerInterface $entityManager,
    ): Response {
        /**
         * @var SignalementBailleur $user
         */
        $user = $this->getUser();
        $signalement = $signalementRepository->findOneBy(['uuid' => $user->getUserIdentifier()]);
        $infoDesordres = $signalementDesordresProcessor->process($signalement);
        $dateLimit = $signalement->getCreatedAt()->modify('+3 weeks -1 day');
        $suiviReponse = $suiviRepository->findOneBy(['signalement' => $signalement, 'category' => SuiviCategory::injonctionBailleurReponseCategories()]);

        if ($suiviReponse) {
            $form = null;
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
        } else {
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
        }

        return $this->render('front/dossier_bailleur.html.twig', [
            'signalement' => $signalement,
            'infoDesordres' => $infoDesordres,
            'suiviReponse' => $suiviReponse,
            'dateLimit' => $dateLimit,
            'form' => $form,
        ]);
    }
}
