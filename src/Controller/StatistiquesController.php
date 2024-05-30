<?php

namespace App\Controller;

use App\Repository\TerritoryRepository;
use App\Service\Statistics\BatimentDesordresStatisticProvider;
use App\Service\Statistics\DesordresCategoriesStatisticProvider;
use App\Service\Statistics\GlobalAnalyticsProvider;
use App\Service\Statistics\ListTerritoryStatisticProvider;
use App\Service\Statistics\LogementDesordresStatisticProvider;
use App\Service\Statistics\MonthStatisticProvider;
use App\Service\Statistics\MotifClotureStatisticProvider;
use App\Service\Statistics\StatusStatisticProvider;
use App\Service\Statistics\TerritoryStatisticProvider;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatistiquesController extends AbstractController
{
    private $ajaxResult;

    public function __construct(
        private GlobalAnalyticsProvider $globalAnalyticsProvider,
        private ListTerritoryStatisticProvider $listTerritoryStatisticProvider,
        private TerritoryStatisticProvider $territoryStatisticProvider,
        private MonthStatisticProvider $monthStatisticProvider,
        private StatusStatisticProvider $statusStatisticProvider,
        private DesordresCategoriesStatisticProvider $desordresCategoriesStatisticProvider,
        private LogementDesordresStatisticProvider $logementDesordresStatisticProvider,
        private BatimentDesordresStatisticProvider $batimentDesordresStatisticProvider,
        private MotifClotureStatisticProvider $motifClotureStatisticProvider
    ) {
    }

    #[Route('/stats', name: 'front_statistiques')]
    public function statistiques(): Response
    {
        return $this->render('front/statistiques.html.twig');
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
        $this->ajaxResult['percent_refused'] = $globalStatistics['percent_refused'];
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

        $this->ajaxResult['signalement_per_motif_cloture'] = $this->motifClotureStatisticProvider->getData($territory, null, 'bar');
        $this->ajaxResult['signalement_per_motif_cloture_this_year'] = $this->motifClotureStatisticProvider->getData($territory, $currentYear, 'bar');

        $this->ajaxResult['signalement_per_desordres_categories'] = $this->desordresCategoriesStatisticProvider->getData($territory, null);
        $this->ajaxResult['signalement_per_desordres_categories_this_year'] = $this->desordresCategoriesStatisticProvider->getData($territory, $currentYear);

        $this->ajaxResult['signalement_per_logement_desordres'] = $this->logementDesordresStatisticProvider->getData($territory, null);
        $this->ajaxResult['signalement_per_logement_desordres_this_year'] = $this->logementDesordresStatisticProvider->getData($territory, $currentYear);

        $this->ajaxResult['signalement_per_batiment_desordres'] = $this->batimentDesordresStatisticProvider->getData($territory, null);
        $this->ajaxResult['signalement_per_batiment_desordres_this_year'] = $this->batimentDesordresStatisticProvider->getData($territory, $currentYear);

        return $this->json($this->ajaxResult);
    }
}
