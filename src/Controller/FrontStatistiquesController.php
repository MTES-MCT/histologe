<?php

namespace App\Controller;

use App\Repository\TerritoryRepository;
use App\Service\Statistics\GlobalAnalyticsProvider;
use App\Service\Statistics\ListTerritoryStatisticProvider;
use App\Service\Statistics\MonthStatisticProvider;
use App\Service\Statistics\MotifClotureStatisticProvider;
use App\Service\Statistics\SituationStatisticProvider;
use App\Service\Statistics\StatusStatisticProvider;
use App\Service\Statistics\TerritoryStatisticProvider;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontStatistiquesController extends AbstractController
{
    private $ajaxResult;

    public function __construct(
        private GlobalAnalyticsProvider $globalAnalyticsProvider,
        private ListTerritoryStatisticProvider $listTerritoryStatisticProvider,
        private TerritoryStatisticProvider $territoryStatisticProvider,
        private MonthStatisticProvider $monthStatisticProvider,
        private StatusStatisticProvider $statusStatisticProvider,
        private SituationStatisticProvider $situationStatisticProvider,
        private MotifClotureStatisticProvider $motifClotureStatisticProvider
        ) {
    }

    #[Route('/statistiques', name: 'front_statistiques')]
    public function statistiques(): Response
    {
        $title = 'En quelques chiffres';

        return $this->render('front/statistiques.html.twig', [
            'title' => $title,
        ]);
    }

    #[Route('/statistiques-filter', name: 'front_statistiques_filter')]
    public function filter(Request $request, TerritoryRepository $territoryRepository): Response
    {
        $this->ajaxResult = [];
        $this->ajaxResult['response'] = 'success';

        $globalStatistics = $this->globalAnalyticsProvider->getData();
        $this->ajaxResult['count_signalement_resolus'] = $globalStatistics['count_signalement_resolus'];
        $this->ajaxResult['count_signalement'] = $globalStatistics['count_signalement'];
        $this->ajaxResult['count_territory'] = $globalStatistics['count_territory'];
        $this->ajaxResult['percent_validation'] = $globalStatistics['percent_validation'];
        $this->ajaxResult['percent_cloture'] = $globalStatistics['percent_cloture'];
        $this->ajaxResult['count_imported'] = $globalStatistics['count_imported'];

        $this->ajaxResult['list_territoires'] = $this->listTerritoryStatisticProvider->getData();
        $this->ajaxResult['signalement_per_territoire'] = $this->territoryStatisticProvider->getData();

        $territory = null;
        $requestTerritory = $request->get('territoire');
        if ('' !== $requestTerritory && 'all' !== $requestTerritory) {
            $territory = $territoryRepository->findOneBy(['id' => $requestTerritory]);
        }
        $currentDate = new DateTime();
        $currentYear = $currentDate->format('Y');

        $this->ajaxResult['signalement_per_month'] = $this->monthStatisticProvider->getData($territory, null);
        $this->ajaxResult['signalement_per_month_this_year'] = $this->monthStatisticProvider->getData($territory, $currentYear);

        $this->ajaxResult['signalement_per_statut'] = $this->statusStatisticProvider->getData($territory, null);
        $this->ajaxResult['signalement_per_statut_this_year'] = $this->statusStatisticProvider->getData($territory, $currentYear);

        $this->ajaxResult['signalement_per_situation'] = $this->situationStatisticProvider->getData($territory, null);
        $this->ajaxResult['signalement_per_situation_this_year'] = $this->situationStatisticProvider->getData($territory, $currentYear);

        $this->ajaxResult['signalement_per_motif_cloture'] = $this->motifClotureStatisticProvider->getData($territory, null);
        $this->ajaxResult['signalement_per_motif_cloture_this_year'] = $this->motifClotureStatisticProvider->getData($territory, $currentYear);

        return $this->json($this->ajaxResult);
    }
}
