<?php

namespace App\Controller\Back;

use App\Entity\Territory;
use App\Entity\User;
use App\Repository\TerritoryRepository;
use App\Service\Statistics\FilteredBackAnalyticsProvider;
use App\Service\Statistics\GlobalBackAnalyticsProvider;
use App\Service\Statistics\ListCommunesStatisticProvider;
use App\Service\Statistics\ListTagsStatisticProvider;
use App\Service\Statistics\ListTerritoryStatisticProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/statistiques')]
class BackStatistiquesController extends AbstractController
{
    private array $ajaxResult;

    public function __construct(
        private ListTerritoryStatisticProvider $listTerritoryStatisticProvider,
        private ListCommunesStatisticProvider $listCommunesStatisticProvider,
        private ListTagsStatisticProvider $listTagStatisticProvider,
        private GlobalBackAnalyticsProvider $globalBackAnalyticsProvider,
        private FilteredBackAnalyticsProvider $filteredBackAnalyticsProvider,
        ) {
    }

    #[Route('/', name: 'back_statistiques')]
    public function index(): Response
    {
        $title = 'Statistiques';

        return $this->render('back/statistiques/index.html.twig', [
            'title' => $title,
        ]);
    }

    /**
     * Route called by Ajax requests (filters filtered by user type, statistics filtered by filters).
     */
    #[Route('/filter', name: 'back_statistiques_filter')]
    public function filter(Request $request, TerritoryRepository $territoryRepository): Response
    {
        if ($this->getUser()) {
            $this->ajaxResult = [];

            $territory = $this->getSelectedTerritory($request, $territoryRepository);

            $this->buildFilterLists($territory);

            $globalStatistics = $this->globalBackAnalyticsProvider->getData($territory);
            $this->ajaxResult['count_signalement'] = $globalStatistics['count_signalement'];
            $this->ajaxResult['average_criticite'] = $globalStatistics['average_criticite'];
            $this->ajaxResult['average_days_validation'] = $globalStatistics['average_days_validation'];
            $this->ajaxResult['average_days_closure'] = $globalStatistics['average_days_closure'];

            $this->filteredBackAnalyticsProvider->initFilters($request, $territory);
            $filteredStatistics = $this->filteredBackAnalyticsProvider->getData();
            $this->ajaxResult['count_signalement_filtered'] = $filteredStatistics['count_signalement_filtered'];
            $this->ajaxResult['average_criticite_filtered'] = $filteredStatistics['average_criticite_filtered'];
            $this->ajaxResult['countSignalementPerMonth'] = $filteredStatistics['count_signalement_per_month'];
            $this->ajaxResult['countSignalementPerPartenaire'] = $filteredStatistics['count_signalement_per_partenaire'];
            $this->ajaxResult['countSignalementPerSituation'] = $filteredStatistics['count_signalement_per_situation'];
            $this->ajaxResult['countSignalementPerCriticite'] = $filteredStatistics['count_signalement_per_criticite'];
            $this->ajaxResult['countSignalementPerStatut'] = $filteredStatistics['count_signalement_per_statut'];
            $this->ajaxResult['countSignalementPerCriticitePercent'] = $filteredStatistics['count_signalement_per_criticite_percent'];
            $this->ajaxResult['countSignalementPerVisite'] = $filteredStatistics['count_signalement_per_visite'];
            $this->ajaxResult['countSignalementPerMotifCloture'] = $filteredStatistics['count_signalement_per_motif_cloture'];

            $this->ajaxResult['response'] = 'success';

            return $this->json($this->ajaxResult);
        }

        return $this->json(['response' => 'error'], 400);
    }

    private function getSelectedTerritory(Request $request, TerritoryRepository $territoryRepository): ?Territory
    {
        // If Super Admin, we should be able to filter on Territoire
        $territory = null;
        if ($this->isGranted('ROLE_ADMIN')) {
            $requestTerritoire = $request->get('territoire');
            if ('' !== $requestTerritoire && 'all' !== $requestTerritoire) {
                $territory = $territoryRepository->findOneBy(['id' => $requestTerritoire]);
            }
        } else {
            /** @var User $user */
            $user = $this->getUser();
            $territory = $user->getTerritory();
        }

        return $territory;
    }

    /**
     * Build lists of data that will be returned as filters.
     */
    private function buildFilterLists(?Territory $territory)
    {
        // Tells Vue component if a user can filter through Territoire
        $this->ajaxResult['can_filter_territoires'] = $this->isGranted('ROLE_ADMIN') ? '1' : '0';
        $this->ajaxResult['can_see_per_partenaire'] = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_ADMIN_TERRITORY') ? '1' : '0';

        if ($this->isGranted('ROLE_ADMIN')) {
            $this->ajaxResult['list_territoires'] = $this->listTerritoryStatisticProvider->getData();
        }
        $this->ajaxResult['list_communes'] = $this->listCommunesStatisticProvider->getData($territory);
        $this->ajaxResult['list_etiquettes'] = $this->listTagStatisticProvider->getData($territory);
    }
}
