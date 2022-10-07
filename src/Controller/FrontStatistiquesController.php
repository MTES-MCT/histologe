<?php

namespace App\Controller;

use App\Repository\TerritoryRepository;
use App\Service\Statistics\CountSignalementPerMonthStatisticProvider;
use App\Service\Statistics\CountSignalementPerMotifClotureStatisticProvider;
use App\Service\Statistics\CountSignalementPerSituationStatisticProvider;
use App\Service\Statistics\CountSignalementPerStatusStatisticProvider;
use App\Service\Statistics\CountSignalementPerTerritoryStatisticProvider;
use App\Service\Statistics\CountSignalementStatisticProvider;
use App\Service\Statistics\CountTerritoryStatisticProvider;
use App\Service\Statistics\ListTerritoryStatisticProvider;
use App\Service\Statistics\PercentSignalementClosedStatisticProvider;
use App\Service\Statistics\PercentSignalementValidatedStatisticProvider;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontStatistiquesController extends AbstractController
{
    private $ajaxResult;

    public function __construct(
        private CountSignalementStatisticProvider $countSignalementStatisticProvider,
        private CountTerritoryStatisticProvider $countTerritoryStatisticProvider,
        private PercentSignalementValidatedStatisticProvider $percentSignalementValidatedStatisticProvider,
        private PercentSignalementClosedStatisticProvider $percentSignalementClosedStatisticProvider,
        private ListTerritoryStatisticProvider $listTerritoryStatisticProvider,
        private CountSignalementPerTerritoryStatisticProvider $countSignalementPerTerritoryStatisticProvider,
        private CountSignalementPerMonthStatisticProvider $countSignalementPerMonthStatisticProvider,
        private CountSignalementPerStatusStatisticProvider $countSignalementPerStatusStatisticProvider,
        private CountSignalementPerSituationStatisticProvider $countSignalementPerSituationStatisticProvider,
        private CountSignalementPerMotifClotureStatisticProvider $countSignalementPerMotifClotureStatisticProvider
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
        $this->ajaxResult['count_signalement'] = $this->countSignalementStatisticProvider->getData();
        $this->ajaxResult['count_territory'] = $this->countTerritoryStatisticProvider->getData();
        $this->ajaxResult['percent_validation'] = $this->percentSignalementValidatedStatisticProvider->getData();
        $this->ajaxResult['percent_cloture'] = $this->percentSignalementClosedStatisticProvider->getData();
        $this->ajaxResult['list_territoires'] = $this->listTerritoryStatisticProvider->getData();
        $this->ajaxResult['signalement_per_territoire'] = $this->countSignalementPerTerritoryStatisticProvider->getData();

        $territory = null;
        $requestTerritory = $request->get('territoire');
        if ('' !== $requestTerritory && 'all' !== $requestTerritory) {
            $territory = $territoryRepository->findOneBy(['id' => $requestTerritory]);
        }
        $currentDate = new DateTime();
        $currentYear = $currentDate->format('Y');

        $this->ajaxResult['signalement_per_month'] = $this->countSignalementPerMonthStatisticProvider->getData($territory, null);
        $this->ajaxResult['signalement_per_month_this_year'] = $this->countSignalementPerMonthStatisticProvider->getData($territory, $currentYear);

        $this->ajaxResult['signalement_per_statut'] = $this->countSignalementPerStatusStatisticProvider->getData($territory, null);
        $this->ajaxResult['signalement_per_statut_this_year'] = $this->countSignalementPerStatusStatisticProvider->getData($territory, $currentYear);

        $this->ajaxResult['signalement_per_situation'] = $this->countSignalementPerSituationStatisticProvider->getData($territory, null);
        $this->ajaxResult['signalement_per_situation_this_year'] = $this->countSignalementPerSituationStatisticProvider->getData($territory, $currentYear);

        $this->ajaxResult['signalement_per_motif_cloture'] = $this->countSignalementPerMotifClotureStatisticProvider->getData($territory, null);
        $this->ajaxResult['signalement_per_motif_cloture_this_year'] = $this->countSignalementPerMotifClotureStatisticProvider->getData($territory, $currentYear);

        return $this->json($this->ajaxResult);
    }
}
