<?php

namespace App\Controller\Back;

use App\Dto\StatisticsFilters;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\TerritoryRepository;
use App\Service\Signalement\SearchFilterOptionDataProvider;
use App\Service\Statistics\FilteredBackAnalyticsProvider;
use App\Service\Statistics\GlobalBackAnalyticsProvider;
use App\Service\Statistics\ListCommunesStatisticProvider;
use App\Service\Statistics\ListEpciStatisticProvider;
use App\Service\Statistics\ListTagsStatisticProvider;
use App\Service\Statistics\ListTerritoryStatisticProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/bo/statistiques')]
class BackStatistiquesController extends AbstractController
{
    private array $result;

    public function __construct(
        private ListTerritoryStatisticProvider $listTerritoryStatisticProvider,
        private ListCommunesStatisticProvider $listCommunesStatisticProvider,
        private ListEpciStatisticProvider $listEpciStatisticProvider,
        private ListTagsStatisticProvider $listTagStatisticProvider,
        private GlobalBackAnalyticsProvider $globalBackAnalyticsProvider,
        private FilteredBackAnalyticsProvider $filteredBackAnalyticsProvider,
        private TagAwareCacheInterface $cache,
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
        $partners = new ArrayCollection($this->getSelectedPartners()->toArray());

        $this->buildFilterLists($territory);

        $territoryKey = !empty($territory) ? $territory->getZip() : '';
        $partnersIds = implode('-', $partners->map(fn ($partner) => $partner->getId())->toArray());
        $cacheKey = 'backGlobalStatistics-zip-'.$territoryKey.'-partners-'.$partnersIds;
        $backGlobalStatistics = $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($territory, $partners) {
                $item->expiresAfter(3600); // 1 hour for global back stats
                $item->tag([SearchFilterOptionDataProvider::CACHE_TAG.$territory->getZip()]);

                return $this->globalBackAnalyticsProvider->getData($territory, $partners);
            }
        );

        $this->result['count_signalement'] = $backGlobalStatistics['count_signalement'];
        $this->result['average_criticite'] = $backGlobalStatistics['average_criticite'];
        $this->result['average_days_validation'] = $backGlobalStatistics['average_days_validation'];
        $this->result['average_days_closure'] = $backGlobalStatistics['average_days_closure'];
        $this->result['count_signalement_refuses'] = $backGlobalStatistics['count_signalement_refuses'];
        $this->result['count_signalement_archives'] = $backGlobalStatistics['count_signalement_archives'];

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
        $epcis = json_decode($request->get('epcis'));
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
            $epcis,
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
        if (1 === $user->getPartners()->count() && !$this->isGranted('ROLE_ADMIN')) {
            return $user->getPartners()->first()->getTerritory();
        }
        $authorizedTerritories = $user->getPartnersTerritories();
        $territoryId = $request->get('territoire');
        if (!$territoryId || 'all' === $territoryId) {
            return null;
        }
        if ($this->isGranted('ROLE_ADMIN') || isset($authorizedTerritories[$territoryId])) {
            return $territoryRepository->find($territoryId);
        }

        return null;
    }

    /**
     * Build lists of data that will be returned as filters.
     */
    private function buildFilterLists(?Territory $territory)
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->result['can_filter_territoires'] = '0';
        $this->result['can_filter_archived'] = $this->isGranted('ROLE_ADMIN') ? '1' : '0';
        $this->result['can_see_per_partenaire'] = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_ADMIN_TERRITORY') ? '1' : '0';

        if ($this->isGranted('ROLE_ADMIN') || count($user->getPartnersTerritories()) > 1) {
            $this->result['can_filter_territoires'] = '1';
            $this->result['list_territoires'] = $this->listTerritoryStatisticProvider->getData($user);
        }

        $territoryKey = !empty($territory) ? $territory->getZip() : '';
        $cacheKey = 'back-statistiques-filters-zip-'.$territoryKey;
        $filterLists = $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($territory) {
                $item->expiresAfter(7200); // 2 hours for filters
                $item->tag([SearchFilterOptionDataProvider::CACHE_TAG.$territory->getZip()]);

                return [
                    'list_communes' => $this->listCommunesStatisticProvider->getData($territory),
                    'list_epcis' => $this->listEpciStatisticProvider->getData($territory),
                    'list_etiquettes' => $this->listTagStatisticProvider->getData($territory),
                ];
            }
        );

        $this->result['list_communes'] = $filterLists['list_communes'];
        $this->result['list_epcis'] = $filterLists['list_epcis'];
        $this->result['list_etiquettes'] = $filterLists['list_etiquettes'];
    }
}
