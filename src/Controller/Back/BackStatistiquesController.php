<?php

namespace App\Controller\Back;

use App\Entity\Tag;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Repository\TagRepository;
use App\Repository\TerritoryRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/statistiques')]
class BackStatistiquesController extends AbstractController
{
    private array $filterResult;
    private DateTime $filterDateStart;
    private DateTime $filterDateEnd;

    /**
     * Route to access Statistiques in the back-office.
     */
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
    public function filter(Request $request, TagRepository $tagsRepository, SignalementRepository $signalementRepository, TerritoryRepository $territoryRepository): Response
    {
        if ($this->getUser()) {
            $this->filterResult = [];

            /**
             * @var User $user
             */
            $user = $this->getUser();
            $territory = $user->getTerritory();

            $this->buildLists($request, $territory, $tagsRepository, $territoryRepository);
            $result = $this->buildQuery($request, $signalementRepository, $territory);

            // Count stats
            $totalCriticite = 0;
            $countHasDaysValidation = 0;
            $totalDaysValidation = 0;
            $countHasDaysClosure = 0;
            $totalDaysClosure = 0;
            $listMonthName = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
            $countSignalementPerMonth = [];
            for ($year = $this->filterDateStart->format('Y'); $year <= $this->filterDateEnd->format('Y'); ++$year) {
                $monthStart = 0;
                if ($year == $this->filterDateStart->format('Y')) {
                    $monthStart = $this->filterDateStart->format('m') - 1;
                }
                $monthEnd = 11;
                if ($year == $this->filterDateEnd->format('Y')) {
                    $monthEnd = $this->filterDateEnd->format('m') - 1;
                }
                for ($month = $monthStart; $month <= $monthEnd; ++$month) {
                    $countSignalementPerMonth[$listMonthName[$month].' '.$year] = 0;
                }
            }
            /**
             * @var Signalement $signalementItem
             */
            foreach ($result as $signalementItem) {
                $totalCriticite += $signalementItem->getScoreCreation();
                $dateCreatedAt = $signalementItem->getCreatedAt();
                if (null !== $dateCreatedAt) {
                    $dateValidatedAt = $signalementItem->getValidatedAt();
                    if (null !== $dateValidatedAt) {
                        ++$countHasDaysValidation;
                        $dateDiff = $dateCreatedAt->diff($dateValidatedAt);
                        $totalDaysValidation += $dateDiff->d;
                    }
                    $dateClosedAt = $signalementItem->getClosedAt();
                    if (null !== $dateClosedAt) {
                        ++$countHasDaysClosure;
                        $dateDiff = $dateCreatedAt->diff($dateClosedAt);
                        $totalDaysClosure += $dateDiff->d;
                    }

                    $month_name = $listMonthName[$dateCreatedAt->format('m') - 1].' '.$dateCreatedAt->format('Y');
                    if (empty($countSignalementPerMonth[$month_name])) {
                        $countSignalementPerMonth[$month_name] = 0;
                    }
                    ++$countSignalementPerMonth[$month_name];
                }
            }

            $countSignalement = \count($result);
            $averageCriticite = $countSignalement > 0 ? round($totalCriticite / $countSignalement) : '-';
            $averageDaysValidation = $countHasDaysValidation > 0 ? round($totalDaysValidation * 10 / $countHasDaysValidation) / 10 : '-';
            $averageDaysClosure = $countHasDaysClosure > 0 ? round($totalDaysClosure * 10 / $countHasDaysClosure) / 10 : '-';

            $this->filterResult['count_signalement'] = $countSignalement;
            $this->filterResult['average_criticite'] = $averageCriticite;
            $this->filterResult['average_days_validation'] = $averageDaysValidation;
            $this->filterResult['average_days_closure'] = $averageDaysClosure;

            $this->filterResult['countSignalementPerMonth'] = $countSignalementPerMonth;
            $this->filterResult['countSignalementPerPartenaire'] = [];
            $this->filterResult['countSignalementPerSituation'] = [];
            $this->filterResult['countSignalementPerCriticite'] = [];
            $this->filterResult['countSignalementPerStatut'] = [];
            $this->filterResult['countSignalementPerCriticitePercent'] = [];
            $this->filterResult['countSignalementPerVisite'] = [];

            $this->filterResult['response'] = 'success';

            return $this->json($this->filterResult);
        }

        return $this->json(['response' => 'error'], 400);
    }

    /**
     * Build lists of data that will be returned as filters.
     */
    private function buildLists(Request $request, Territory $territory, TagRepository $tagsRepository, TerritoryRepository $territoryRepository)
    {
        // Tells Vue component if a user can filter through Territoire
        $this->filterResult['can_filter_territoires'] = $this->isGranted('ROLE_ADMIN') ? '1' : '0';

        // If Super Admin
        // Returns the list of available Territoire
        if ($this->isGranted('ROLE_ADMIN')) {
            $this->filterResult['list_territoires'] = [];
            $territoryList = $territoryRepository->findAllList();
            /**
             * @var Territory $territoryItem
             */
            foreach ($territoryList as $territoryItem) {
                $this->filterResult['list_territoires'][$territoryItem->getId()] = $territoryItem->getName();
            }

            $request_territoire = $request->get('territoire');
            if ('' !== $request_territoire && 'all' !== $request_territoire) {
                $territory = $territoryRepository->findOneBy(['id' => $request_territoire]);
            }
        }

        // List of the Communnes linked to a User
        // - if user/admin of Territoire: only Communes from a Territoire (in the BAN)
        // - if super admin: every Communes
        $this->filterResult['list_communes'] = [];

        // List of the Etiquettes linked to a User
        // - if user/admin of Territoire: only Etiquettes from a Territoire
        // - if super admin: every Etiquettes of the platform
        $tagList = $tagsRepository->findAllActive($territory);
        /*
        * @var Tag $tagItem
        */
        $this->filterResult['list_etiquettes'] = [];
        foreach ($tagList as $tagItem) {
            $this->filterResult['list_etiquettes'][$tagItem->getId()] = $tagItem->getLabel();
        }
    }

    /**
     * Query list of Signalement, filtered with params.
     */
    private function buildQuery(Request $request, SignalementRepository $signalementRepository, Territory $territory)
    {
        $communes = $request->get('communes');
        $statut = $request->get('statut');
        $etiquettes = $request->get('etiquettes');
        $type = $request->get('type');
        $dateStart = $request->get('dateStart');
        $this->filterDateStart = new DateTime($dateStart);
        $dateEnd = $request->get('dateEnd');
        $this->filterDateEnd = new DateTime($dateEnd);
        $countRefused = $request->get('countRefused');
        $hasCountRefused = '1' == $countRefused;
        $territoryFilter = $territory ? $territory->getId() : null;

        return $signalementRepository->findByFilters($statut, $hasCountRefused, $this->filterDateStart, $this->filterDateEnd, $type, $territoryFilter);
    }
}
