<?php

namespace App\Controller\Back;

use App\Dto\StatisticsFilters;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\TerritoryRepository;
use App\Service\Statistics\FilteredBackAnalyticsProvider;
use App\Service\Statistics\GlobalBackAnalyticsProvider;
use App\Service\Statistics\ListCommunesStatisticProvider;
use App\Service\Statistics\ListTagsStatisticProvider;
use App\Service\Statistics\ListTerritoryStatisticProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/statistiques')]
class BackStatistiquesController extends AbstractController
{
    private array $result;

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
        $this->result = [];

        $territory = $this->getSelectedTerritory($request, $territoryRepository);
        $partners = $this->getSelectedPartners();

        $this->buildFilterLists($territory);

        $globalStatistics = $this->globalBackAnalyticsProvider->getData($territory, $partners);
        $this->result['count_signalement'] = $globalStatistics['count_signalement'];
        $this->result['average_criticite'] = $globalStatistics['average_criticite'];
        $this->result['average_days_validation'] = $globalStatistics['average_days_validation'];
        $this->result['average_days_closure'] = $globalStatistics['average_days_closure'];
        $this->result['count_signalement_refuses'] = $globalStatistics['count_signalement_refuses'];
        $this->result['count_signalement_archives'] = $globalStatistics['count_signalement_archives'];

        $statisticsFilters = $this->createFilters($request, $territory, $partners);
        $filteredStatistics = $this->filteredBackAnalyticsProvider->getData($statisticsFilters);
        $this->result['count_signalement_filtered'] = $filteredStatistics['count_signalement_filtered'];
        $this->result['average_criticite_filtered'] = $filteredStatistics['average_criticite_filtered'];
        $this->result['countSignalementPerMonth'] = $filteredStatistics['count_signalement_per_month'];
        $this->result['countSignalementPerPartenaire'] = $filteredStatistics['count_signalement_per_partenaire'];
        $this->result['countSignalementPerSituation'] = $filteredStatistics['count_signalement_per_situation'];
        $this->result['countSignalementPerCriticite'] = $filteredStatistics['count_signalement_per_criticite'];
        $this->result['countSignalementPerStatut'] = $filteredStatistics['count_signalement_per_statut'];
        $this->result['countSignalementPerCriticitePercent'] = $filteredStatistics['count_signalement_per_criticite_percent'];
        $this->result['countSignalementPerVisite'] = $filteredStatistics['count_signalement_per_visite'];
        $this->result['countSignalementPerMotifCloture'] = $filteredStatistics['count_signalement_per_motif_cloture'];

        $this->result['response'] = 'success';

        return $this->json($this->result);
    }

    private function createFilters(Request $request, ?Territory $territory, ArrayCollection $partners): StatisticsFilters
    {
        $communes = json_decode($request->get('communes'));
        $statut = $request->get('statut');
        $strEtiquettes = json_decode($request->get('etiquettes') ?? '[]');
        $etiquettes = array_map(fn ($value): int => $value * 1, $strEtiquettes);
        $type = $request->get('type');
        $dateStartInput = $request->get('dateStart');
        $dateStart = (null !== $dateStartInput) ? new \DateTime($dateStartInput) : null;
        $dateEndInput = $request->get('dateEnd');
        $dateEnd = (null !== $dateEndInput) ? new \DateTime($dateEndInput) : null;
        $hasCountRefused = '1' == $request->get('countRefused');
        $hasCountArchived = '1' == $request->get('countArchived');

        return new StatisticsFilters(
            $communes,
            $statut,
            $etiquettes,
            $type,
            $dateStart,
            $dateEnd,
            $hasCountRefused,
            $hasCountArchived,
            $territory,
            $partners
        );
    }

    private function getSelectedPartners(): Collection
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user->isUserPartner() || $user->isPartnerAdmin()) {
            return $user->getPartners();
        }

        return new ArrayCollection();
    }

    private function getSelectedTerritory(Request $request, TerritoryRepository $territoryRepository): ?Territory
    {
        /** @var User $user */
        $user = $this->getUser();
        $enabledTerritories = $user->getPartnersTerritories();
        $territoryId = $request->get('territoire');
        if (!$territoryId || 'all' === $territoryId) {
            $territoryId = null;
        }
        $territory = null;
        if ($territoryId && ($this->isGranted('ROLE_ADMIN') || isset($enabledTerritories[$territoryId]))) {
            $territory = $territoryRepository->find($territoryId);
        }
        if (!$territory && !$this->isGranted('ROLE_ADMIN')) {
            $territory = $user->getFirstTerritory();
        }

        return $territory;
    }

    /**
     * Build lists of data that will be returned as filters.
     */
    private function buildFilterLists(?Territory $territory)
    {
        // Tells Vue component if a user can filter through Territoire
        $this->result['can_filter_territoires'] = $this->isGranted('ROLE_ADMIN') ? '1' : '0';
        $this->result['can_filter_archived'] = $this->isGranted('ROLE_ADMIN') ? '1' : '0';
        $this->result['can_see_per_partenaire'] = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_ADMIN_TERRITORY') ? '1' : '0';

        if ($this->isGranted('ROLE_ADMIN')) {
            $this->result['list_territoires'] = $this->listTerritoryStatisticProvider->getData();
        }
        $this->result['list_communes'] = $this->listCommunesStatisticProvider->getData($territory);
        $this->result['list_etiquettes'] = $this->listTagStatisticProvider->getData($territory);
    }
}
