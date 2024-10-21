<?php

namespace App\Controller\Back;

use App\Manager\HistoryEntryManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/history')]
class HistoryEntryController extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'FEATURE_HISTORIQUE_AFFECTATIONS')]
        bool $featureHistoriqueAffectations,
    ) {
        if (!$featureHistoriqueAffectations) {
            throw $this->createNotFoundException();
        }
    }

    #[Route('/affectation', name: 'history_affectation', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN_TERRITORY')]
    public function listHistoryAffectation(
        Request $request,
        HistoryEntryManager $historyEntryManager
    ): Response {
        $signalementId = $request->get('signalementId');
        if ($signalementId) {
            $historyEntries = $historyEntryManager->getAffectationHistory($signalementId);

            return $this->json(['historyEntries' => $historyEntries]);
        }

        return $this->json(['response' => 'error'], 400);
    }
}
