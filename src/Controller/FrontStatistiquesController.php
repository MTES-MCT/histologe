<?php

namespace App\Controller;

use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontStatistiquesController extends AbstractController
{
    private $ajaxResult;

    #[Route('/statistiques', name: 'front_statistiques')]
    public function statistiques(): Response
    {
        $title = 'Statistiques';

        $stats = [];
        $stats['total'] = '11 979';
        $stats['pris_en_compte'] = '99,8';
        $stats['clotures'] = '68,9';
        $stats['nb_territoires'] = '15';
        $stats['moyenne_nb_desordres_par_signalement'] = '4,4';
        $stats['moyenne_jours_resolution'] = '225';
        $stats['moyenne_criticite'] = '28,9';

        return $this->render('front/statistiques.html.twig', [
            'title' => $title,
            'stats' => $stats,
        ]);
    }

    #[Route('/statistiques-filter', name: 'front_statistiques_filter')]
    public function filter(Request $request, TerritoryRepository $territoryRepository, SignalementRepository $signalementRepository): Response
    {
        $this->ajaxResult = [];

        $ajaxResult['list_territoires'] = [];
        $territoryList = $territoryRepository->findAllList();
        /**
         * @var Territory $territoryItem
         */
        foreach ($territoryList as $territoryItem) {
            $this->ajaxResult['list_territoires'][$territoryItem->getId()] = $territoryItem->getName();
        }

        $territory_id = null;
        $territory = null;
        $request_territoire = $request->get('territoire');
        if ('' !== $request_territoire && 'all' !== $request_territoire) {
            $territory_id = $request_territoire;
            $territory = $territoryRepository->findOneBy(['id' => $request_territoire]);
        }

        $this->makeGlobalStats($signalementRepository, $territoryRepository, $territoryList);

        $this->ajaxResult['response'] = 'success';

        return $this->json($this->ajaxResult);
    }

    private function makeGlobalStats(SignalementRepository $signalementRepository, TerritoryRepository $territoryRepository, $territoryList)
    {
        $globalSignalement = $signalementRepository->findByFilters('', true, null, null, '', null, null, null);
        $totalSignalement = \count($globalSignalement);
        $this->ajaxResult['count_signalement'] = $totalSignalement;
        $this->ajaxResult['count_territory'] = \count($territoryList);
        $this->ajaxResult['signalement_per_territoire'] = [];

        $totalValidation = 0;
        $totalCloture = 0;
        /**
         * @var Signalement $signalementItem
         */
        foreach ($globalSignalement as $signalementItem) {
            if (Signalement::STATUS_NEED_VALIDATION !== $signalementItem->getStatut() && Signalement::STATUS_REFUSED !== $signalementItem->getStatut()) {
                ++$totalValidation;
            }
            if (Signalement::STATUS_CLOSED === $signalementItem->getStatut()) {
                ++$totalCloture;
            }

            $territoryId = $signalementItem->getTerritory()->getId();
            if (empty($this->ajaxResult['signalement_per_territoire'][$territoryId])) {
                $territory = $territoryRepository->findOneBy(['id' => $territoryId]);
                $this->ajaxResult['signalement_per_territoire'][$territoryId] = [
                    'name' => $territory->getName(),
                    'zip' => $territory->getZip(), // TODO : pourquoi la valeur est aléatoire ??
                    'count' => 0,
                ];
            }
            ++$this->ajaxResult['signalement_per_territoire'][$signalementItem->getTerritory()->getId()]['count'];
        }

        $percentValidation = $totalSignalement > 0 ? round($totalValidation / $totalSignalement * 1000) / 10 : '-';
        $percentCloture = $totalSignalement > 0 ? round($totalCloture / $totalSignalement * 1000) / 10 : '-';
        $this->ajaxResult['percent_validation'] = $percentValidation;
        $this->ajaxResult['percent_cloture'] = $percentCloture;
    }
}
