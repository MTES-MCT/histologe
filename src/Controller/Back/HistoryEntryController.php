<?php

namespace App\Controller\Back;

use App\Manager\HistoryEntryManager;
use App\Repository\HistoryEntryRepository;
use App\Repository\SignalementRepository;
use App\Security\Voter\SignalementVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/history')]
class HistoryEntryController extends AbstractController
{
    #[Route('/signalement/{id}/affectations', name: 'history_affectation', methods: ['GET'])]
    public function listHistoryAffectation(
        string $id,
        HistoryEntryManager $historyEntryManager,
        SignalementRepository $signalementRepository,
    ): Response {
        $signalement = $signalementRepository->find($id);
        if (
            !$signalement
            || !$this->isGranted(SignalementVoter::SIGN_VIEW, $signalement)
            || !$this->isGranted(SignalementVoter::SIGN_AFFECTATION_SEE, $signalement)
        ) {
            return $this->json(['response' => 'error'], Response::HTTP_FORBIDDEN);
        }

        $historyEntries = $historyEntryManager->getAffectationHistory($signalement);

        return $this->json(['historyEntries' => $historyEntries]);
    }

    #[Route('/diff/{entity_name}/{entity_id}', name: 'back_history_entry_details', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function historyEntryDetails(
        string $entity_id,
        string $entity_name,
        HistoryEntryRepository $historyEntryRepository,
        SignalementRepository $signalementRepository,
    ): Response {
        $historyEntries = $historyEntryRepository->findBy(['entityId' => $entity_id, 'entityName' => 'App\\Entity\\'.$entity_name], ['createdAt' => 'ASC']);
        $entityUrl = null;
        if ('Signalement' === $entity_name && $signalement = $signalementRepository->find($entity_id)) {
            $entityUrl = $this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUUid()]);
        }

        return $this->render('back/history-entry/details.html.twig', [
            'entityId' => $entity_id,
            'entityName' => $entity_name,
            'entityUrl' => $entityUrl,
            'historyEntries' => $historyEntries,
        ]);
    }
}
