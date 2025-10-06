<?php

namespace App\Controller;

use App\Repository\SignalementRepository;
use App\Security\User\SignalementBailleur;
use App\Service\Signalement\SignalementDesordresProcessor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dossier-bailleur')]
class SuiviBailleurController extends AbstractController
{
    #[Route('/', name: 'front_dossier_bailleur', methods: ['GET', 'POST'])]
    public function dossierBailleur(
        SignalementRepository $signalementRepository,
        SignalementDesordresProcessor $signalementDesordresProcessor,
    ): Response {
        /**
         * @var SignalementBailleur $user
         */
        $user = $this->getUser();
        $signalement = $signalementRepository->findOneBy(['uuid' => $user->getUserIdentifier()]);
        $infoDesordres = $signalementDesordresProcessor->process($signalement);

        return $this->render('front/dossier_bailleur.html.twig', [
            'signalement' => $signalement,
            'infoDesordres' => $infoDesordres,
        ]);
    }
}
