<?php

namespace App\Controller\Back;

use App\Manager\HistoryEntryManager;
use App\Repository\SignalementRepository;
use App\Security\Voter\SignalementVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/history')]
class HistoryEntryController extends AbstractController
{
    #[Route('/signalement/{id}/affectations', name: 'history_affectation', methods: ['GET'])]
    public function listHistoryAffectation(
        Request $request,
        HistoryEntryManager $historyEntryManager,
        SignalementRepository $signalementRepository,
    ): Response {
        $signalement = $signalementRepository->find($request->get('id'));
        if (
            !$signalement
            || !$this->isGranted(SignalementVoter::SIGN_VIEW, $signalement)
            || !$this->isGranted(SignalementVoter::AFFECTATION_SEE, $signalement)
        ) {
            return $this->json(['response' => 'error'], Response::HTTP_FORBIDDEN);
        }

        $historyEntries = $historyEntryManager->getAffectationHistory($signalement);

        return $this->json(['historyEntries' => $historyEntries]);
    }
}
