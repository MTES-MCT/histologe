<?php

namespace App\Controller\Back;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Service\Statistics\FilteredBackAnalyticsProvider;
use App\Service\Statistics\GlobalBackAnalyticsProvider;
use App\Service\Statistics\ListCommunesStatisticProvider;
use App\Service\Statistics\ListTagsStatisticProvider;
use App\Service\Statistics\ListTerritoryStatisticProvider;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/statistiques')]
class BackStatistiquesController extends AbstractController
{
    private array $ajaxResult;
    private string $filterStatut;
    private DateTime $filterDateStart;
    private DateTime $filterDateEnd;

    private const CRITICITE_VERY_WEAK = '< 25 %';
    private const CRITICITE_WEAK = 'De 25 à 50 %';
    private const CRITICITE_STRONG = 'De 51 à 75 %';
    private const CRITICITE_VERY_STRONG = '> 75 %';

    private const MONTH_NAMES = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

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
    public function filter(Request $request, SignalementRepository $signalementRepository, TerritoryRepository $territoryRepository): Response
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

            $resultFiltered = $this->buildQuery($request, $signalementRepository, $territory);
            $this->makeFilteredStats($resultFiltered);

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

    /**
     * Query list of Signalement, filtered with params.
     */
    private function buildQuery(Request $request, SignalementRepository $signalementRepository, ?Territory $territory)
    {
        $communes = json_decode($request->get('communes'));
        $this->filterStatut = $request->get('statut');
        $strEtiquettes = $request->get('etiquettes');
        $etiquettes = array_map(fn ($value): int => $value * 1, json_decode($strEtiquettes));
        $type = $request->get('type');
        $dateStart = $request->get('dateStart');
        $this->filterDateStart = new DateTime($dateStart);
        $dateEnd = $request->get('dateEnd');
        $this->filterDateEnd = new DateTime($dateEnd);
        $hasCountRefused = '1' == $request->get('countRefused');
        $territoryFilter = $territory ? $territory->getId() : null;

        return $signalementRepository->findByFilters($this->filterStatut, $hasCountRefused, $this->filterDateStart, $this->filterDateEnd, $type, $territoryFilter, $etiquettes, $communes);
    }

    /**
     * Fill result table with filtered stats.
     */
    private function makeFilteredStats(array $signalements): void
    {
        $totalCriticite = 0;
        $countHasDaysValidation = 0;
        $totalDaysValidation = 0;
        $countHasDaysClosure = 0;
        $totalDaysClosure = 0;
        $countSignalementPerMonth = [];
        $countSignalementPerStatut = [];
        $countSignalementPerCriticitePercent = self::initPerCriticitePercent();
        $countSignalementPerVisite = self::initPerVisite();
        $countSignalementPerMotifCloture = self::initPerMotifCloture();
        $countSignalementPerSituation = [];
        $countSignalementPerCriticite = [];
        $countSignalementPerPartenaire = [];

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
                $countSignalementPerMonth[self::MONTH_NAMES[$month].' '.$year] = 0;
            }
        }

        /** @var Signalement $signalement */
        foreach ($signalements as $signalement) {
            $criticite = $signalement->getScoreCreation();
            $totalCriticite += $criticite;
            $dateCreatedAt = $signalement->getCreatedAt();
            if (null !== $dateCreatedAt) {
                $dateValidatedAt = $signalement->getValidatedAt();
                if (null !== $dateValidatedAt) {
                    ++$countHasDaysValidation;
                    $dateDiff = $dateCreatedAt->diff($dateValidatedAt);
                    $totalDaysValidation += $dateDiff->d;
                }
                $dateClosedAt = $signalement->getClosedAt();
                if (null !== $dateClosedAt) {
                    ++$countHasDaysClosure;
                    $dateDiff = $dateCreatedAt->diff($dateClosedAt);
                    $totalDaysClosure += $dateDiff->d;

                    $strMotifCloture = $signalement->getMotifCloture();
                    if (!empty($strMotifCloture)) {
                        ++$countSignalementPerMotifCloture[$strMotifCloture]['count'];
                    }
                }

                $month_name = self::MONTH_NAMES[$dateCreatedAt->format('m') - 1].' '.$dateCreatedAt->format('Y');
                if (empty($countSignalementPerMonth[$month_name])) {
                    $countSignalementPerMonth[$month_name] = 0;
                }
                ++$countSignalementPerMonth[$month_name];

                $statut = $signalement->getStatut();
                if (empty($countSignalementPerStatut[$statut])) {
                    $countSignalementPerStatut[$statut] = self::initStatutByValue($statut);
                }
                ++$countSignalementPerStatut[$statut]['count'];

                if ($criticite > 75) {
                    ++$countSignalementPerCriticitePercent[self::CRITICITE_VERY_STRONG]['count'];
                } elseif ($criticite >= 51) {
                    ++$countSignalementPerCriticitePercent[self::CRITICITE_STRONG]['count'];
                } elseif ($criticite >= 25) {
                    ++$countSignalementPerCriticitePercent[self::CRITICITE_WEAK]['count'];
                } else {
                    ++$countSignalementPerCriticitePercent[self::CRITICITE_VERY_WEAK]['count'];
                }

                $dateVisite = $signalement->getDateVisite();
                if (empty($dateVisite)) {
                    ++$countSignalementPerVisite['Non']['count'];
                } else {
                    ++$countSignalementPerVisite['Oui']['count'];
                }

                $listSituations = $signalement->getSituations();
                $countListSituations = \count($listSituations);
                for ($i = 0; $i < $countListSituations; ++$i) {
                    $situationStr = $listSituations[$i]->getMenuLabel();
                    if (empty($countSignalementPerSituation[$situationStr])) {
                        $countSignalementPerSituation[$situationStr] = 0;
                    }
                    ++$countSignalementPerSituation[$situationStr];
                }

                $listCriticite = $signalement->getCriticites();
                $countListCriticite = \count($listCriticite);
                for ($i = 0; $i < $countListCriticite; ++$i) {
                    $criticiteStr = $listCriticite[$i]->getLabel();
                    if (empty($countSignalementPerCriticite[$criticiteStr])) {
                        $countSignalementPerCriticite[$criticiteStr] = 0;
                    }
                    ++$countSignalementPerCriticite[$criticiteStr];
                }

                $listAffectations = $signalement->getAffectations();
                $countListAffectations = \count($listAffectations);
                for ($i = 0; $i < $countListAffectations; ++$i) {
                    $affecationItem = $listAffectations[$i];
                    $partner = $listAffectations[$i]->getPartner();
                    $partnerName = $partner->getNom();
                    if (empty($countSignalementPerPartenaire[$partnerName])) {
                        $countSignalementPerPartenaire[$partnerName] = [
                            'total' => 0,
                            'wait' => 0,
                            'accepted' => 0,
                            'refused' => 0,
                            'closed' => 0,
                        ];
                    }
                    ++$countSignalementPerPartenaire[$partnerName]['total'];
                    switch ($affecationItem->getStatut()) {
                        case Affectation::STATUS_ACCEPTED:
                            ++$countSignalementPerPartenaire[$partnerName]['accepted'];
                            break;
                        case Affectation::STATUS_REFUSED:
                            ++$countSignalementPerPartenaire[$partnerName]['refused'];
                            break;
                        case Affectation::STATUS_CLOSED:
                            ++$countSignalementPerPartenaire[$partnerName]['closed'];
                            break;
                        case Affectation::STATUS_WAIT:
                        default:
                            ++$countSignalementPerPartenaire[$partnerName]['wait'];
                            break;
                    }
                }
            }
        }

        foreach ($countSignalementPerPartenaire as $partenaireStr => $partnerStats) {
            $totalPerPartner = $partnerStats['total'];
            $countSignalementPerPartenaire[$partenaireStr]['accepted_percent'] = round($partnerStats['accepted'] / $totalPerPartner * 100);
            $countSignalementPerPartenaire[$partenaireStr]['refused_percent'] = round($partnerStats['refused'] / $totalPerPartner * 100);
            $countSignalementPerPartenaire[$partenaireStr]['closed_percent'] = round($partnerStats['closed'] / $totalPerPartner * 100);
            $countSignalementPerPartenaire[$partenaireStr]['wait_percent'] = round($partnerStats['wait'] / $totalPerPartner * 100);
        }

        $countSignalementFiltered = \count($signalements);
        $averageCriticiteFiltered = $countSignalementFiltered > 0 ? round($totalCriticite / $countSignalementFiltered) : '-';
        arsort($countSignalementPerCriticite);
        $countSignalementPerCriticite = \array_slice($countSignalementPerCriticite, 0, 5);

        $this->ajaxResult['countSignalementPerStatut'] = $countSignalementPerStatut;
        $this->ajaxResult['countSignalementPerCriticitePercent'] = $countSignalementPerCriticitePercent;
        $this->ajaxResult['countSignalementPerVisite'] = $countSignalementPerVisite;
        $this->ajaxResult['countSignalementPerMotifCloture'] = $countSignalementPerMotifCloture;
    }

    /**
     * Init list of Signalement by Statut, to retrieve label and color.
     */
    private static function initStatutByValue(string $statut): array
    {
        $buffer = [
            'label' => '',
            'color' => '',
            'count' => 0,
        ];

        switch ($statut) {
            case Signalement::STATUS_ACTIVE:
            case Signalement::STATUS_NEED_PARTNER_RESPONSE:
                $buffer['label'] = 'En cours';
                $buffer['color'] = '#000091A6';
                break;
            case Signalement::STATUS_CLOSED:
                $buffer['label'] = 'Fermé';
                $buffer['color'] = '#21AB8EA6';
                break;
            case Signalement::STATUS_ARCHIVED:
                $buffer['label'] = 'Archivé';
                $buffer['color'] = '#A558A0';
                break;
            case Signalement::STATUS_REFUSED:
                $buffer['label'] = 'Refusé';
                $buffer['color'] = '#A558A0';
                break;
            case Signalement::STATUS_NEED_VALIDATION:
            default:
                $buffer['label'] = 'Nouveau';
                $buffer['color'] = '#E4794A';
                break;
        }

        return $buffer;
    }

    /**
     * Init list of Signalement by Criticite, to retrieve label and color.
     */
    private static function initPerCriticitePercent(): array
    {
        return [
            self::CRITICITE_VERY_WEAK => [
                'label' => self::CRITICITE_VERY_WEAK,
                'color' => '#21AB8E',
                'count' => 0,
            ],
            self::CRITICITE_WEAK => [
                'label' => self::CRITICITE_WEAK,
                'color' => '#417DC4',
                'count' => 0,
            ],
            self::CRITICITE_STRONG => [
                'label' => self::CRITICITE_STRONG,
                'color' => '#A558A0',
                'count' => 0,
            ],
            self::CRITICITE_VERY_STRONG => [
                'label' => self::CRITICITE_VERY_STRONG,
                'color' => '#E4794A',
                'count' => 0,
            ],
        ];
    }

    /**
     * Init list of Signalement by Visite, to retrieve label and color.
     */
    private static function initPerVisite(): array
    {
        return [
            'Oui' => [
                'label' => 'Oui',
                'color' => '#21AB8E',
                'count' => 0,
            ],
            'Non' => [
                'label' => 'Non',
                'color' => '#E4794A',
                'count' => 0,
            ],
        ];
    }

    /**
     * Init list of Signalement per Motif cloture.
     */
    private static function initPerMotifCloture(): array
    {
        return [
            'RESOLU' => [
                'label' => 'Problème résolu',
                'color' => '#21AB8E',
                'count' => 0,
            ],
            'NON_DECENCE' => [
                'label' => 'Non décence',
                'color' => '#E4794A',
                'count' => 0,
            ],
            'INFRACTION RSD' => [
                'label' => 'Infraction RSD',
                'color' => '#A558A0',
                'count' => 0,
            ],
            'INSALUBRITE' => [
                'label' => 'Insalubrité',
                'color' => '#CE0500',
                'count' => 0,
            ],
            'LOGEMENT DECENT' => [
                'label' => 'Logement décent',
                'color' => '#00A95F',
                'count' => 0,
            ],
            'LOCATAIRE PARTI' => [
                'label' => 'Départ occupant',
                'color' => '#000091',
                'count' => 0,
            ],
            'LOGEMENT VENDU' => [
                'label' => 'Logement vendu',
                'color' => '#417DC4',
                'count' => 0,
            ],
            'AUTRE' => [
                'label' => 'Autre',
                'color' => '#CACAFB',
                'count' => 0,
            ],
        ];
    }
}
