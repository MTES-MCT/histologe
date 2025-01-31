<?php

namespace App\Controller\Back;

use App\Entity\Territory;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Service\Signalement\AutoAssigner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/auto-assigner-simulator')]
class AutoAssignerSimulatorController extends AbstractController
{
    #[Route('/', name: 'back_auto_assigner_simulator_index')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        TerritoryRepository $territoryRepository,
    ): Response {
        $territories = $territoryRepository->findAllWithAutoAffectationRules();

        return $this->render('back/auto-assigner-simulator/index.html.twig', ['territories' => $territories]);
    }

    #[Route('/{territory}', name: 'back_auto_assigner_simulator_territory')]
    #[IsGranted('ROLE_ADMIN')]
    public function territory(
        Request $request,
        Territory $territory,
        SignalementRepository $signalementRepository,
        AutoAssigner $autoAssigner,
    ): Response {
        $results = [];
        $criteria = ['territory' => $territory];
        $limit = $request->query->getInt('limit', 10);
        if ($uuid = $request->query->get('uuid')) {
            $criteria['uuid'] = $uuid;
        }
        $signalements = $signalementRepository->findBy($criteria, ['createdAt' => 'DESC'], $limit);
        foreach ($signalements as $signalement) {
            $assignablePartners = $autoAssigner->assign($signalement, true);
            $results[] = [
                'signalement' => $signalement,
                'assignablePartners' => $assignablePartners,
            ];
        }

        return $this->render('back/auto-assigner-simulator/territory.html.twig', ['territory' => $territory, 'results' => $results, 'limit' => $limit, 'uuid' => $uuid]);
    }
}
