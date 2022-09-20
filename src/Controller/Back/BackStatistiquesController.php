<?php

namespace App\Controller\Back;

use App\Entity\Affectation;
use App\Entity\Signalement;
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
    private array $ajaxResult;
    private string $filterStatut;
    private DateTime $filterDateStart;
    private DateTime $filterDateEnd;

    private const CRITICITE_VERY_WEAK = '< 25 %';
    private const CRITICITE_WEAK = 'De 25 à 50 %';
    private const CRITICITE_STRONG = 'De 51 à 75 %';
    private const CRITICITE_VERY_STRONG = '> 75 %';

    private const MONTH_NAMES = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

    private const COMMUNE_MARSEILLE = 'Marseille';
    private const COMMUNE_LYON = 'Lyon';
    private const COMMUNE_PARIS = 'Paris';

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
            $this->ajaxResult = [];

            /** @var User $user */
            $user = $this->getUser();
            // If Super Admin, we should be able to filter on Territoire
            $territory = $user->getTerritory();
            if ($this->isGranted('ROLE_ADMIN')) {
                $requestTerritoire = $request->get('territoire');
                if ('' !== $requestTerritoire && 'all' !== $requestTerritoire) {
                    $territory = $territoryRepository->findOneBy(['id' => $requestTerritoire]);
                }
            }

            $this->buildFilterLists($territory, $tagsRepository, $territoryRepository);

            $resultGlobal = $this->buildQuerySignalementByTerritory($signalementRepository, $territory);
            // This adds data to $this->ajaxResult
            $this->makeGlobalStats($resultGlobal);

            $resultFiltered = $this->buildQuery($request, $signalementRepository, $territory);
            // This adds data to $this->ajaxResult
            $this->makeFilteredStats($resultFiltered);

            $this->ajaxResult['response'] = 'success';

            return $this->json($this->ajaxResult);
        }

        return $this->json(['response' => 'error'], 400);
    }

    /**
     * Build lists of data that will be returned as filters.
     */
    private function buildFilterLists(?Territory $territory, TagRepository $tagsRepository, TerritoryRepository $territoryRepository)
    {
        // Tells Vue component if a user can filter through Territoire
        $this->ajaxResult['can_filter_territoires'] = $this->isGranted('ROLE_ADMIN') ? '1' : '0';

        if ($this->isGranted('ROLE_ADMIN')) {
            $this->ajaxResult['list_territoires'] = [];
            $territoryList = $territoryRepository->findAllList();
            /** @var Territory $territoryItem */
            foreach ($territoryList as $territoryItem) {
                $this->ajaxResult['list_territoires'][$territoryItem->getId()] = $territoryItem->getName();
            }
        }

        // List of the Communnes linked to a User
        // - if user/admin of Territoire: only Communes from a Territoire (in the BAN)
        // - if super admin: only if selected Territoire
        $this->ajaxResult['list_communes'] = [];
        if (null !== $territory) {
            $communes = $territory->getCommunes();
            /** @var Commune $commune */
            foreach ($communes as $commune) {
                // Controls over 3 Communes with Arrondissements that we don't want
                $nomCommune = $commune->getNom();
                if (preg_match('/('.self::COMMUNE_MARSEILLE.')(.)*(Arrondissement)/', $nomCommune)) {
                    $nomCommune = self::COMMUNE_MARSEILLE;
                }
                if (preg_match('/('.self::COMMUNE_LYON.')(.)*(Arrondissement)/', $nomCommune)) {
                    $nomCommune = self::COMMUNE_LYON;
                }
                if (preg_match('/('.self::COMMUNE_PARIS.')(.)*(Arrondissement)/', $nomCommune)) {
                    $nomCommune = self::COMMUNE_PARIS;
                }
                $this->ajaxResult['list_communes'][$nomCommune] = $nomCommune;
            }
        }

        // List of the Etiquettes linked to a User
        // - if user/admin of Territoire: only Etiquettes from a Territoire
        // - if super admin: only if selected Territoire
        $this->ajaxResult['list_etiquettes'] = [];
        if (null !== $territory) {
            $tagList = $tagsRepository->findAllActive($territory);
            /** @var Tag $tagItem */
            foreach ($tagList as $tagItem) {
                $this->ajaxResult['list_etiquettes'][$tagItem->getId()] = $tagItem->getLabel();
            }
        }
    }

    private function buildQuerySignalementByTerritory(SignalementRepository $signalementRepository, ?Territory $territory): array
    {
        $territoryFilter = $territory ? $territory->getId() : null;

        return $signalementRepository->findByFilters('', false, null, null, '', $territoryFilter, null, null);
    }

    /**
     * Fill result table with global stats.
     */
    private function makeGlobalStats(array $signalements): void
    {
        $totalCriticite = 0;
        $countHasDaysValidation = 0;
        $totalDaysValidation = 0;
        $countHasDaysClosure = 0;
        $totalDaysClosure = 0;
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
                }
            }
        }

        $countSignalement = \count($signalements);
        $averageCriticite = $countSignalement > 0 ? round($totalCriticite / $countSignalement) : '-';
        $averageDaysValidation = $countHasDaysValidation > 0 ? round($totalDaysValidation * 10 / $countHasDaysValidation) / 10 : '-';
        $averageDaysClosure = $countHasDaysClosure > 0 ? round($totalDaysClosure * 10 / $countHasDaysClosure) / 10 : '-';

        $this->ajaxResult['count_signalement'] = $countSignalement;
        $this->ajaxResult['average_criticite'] = $averageCriticite;
        $this->ajaxResult['average_days_validation'] = $averageDaysValidation;
        $this->ajaxResult['average_days_closure'] = $averageDaysClosure;
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

        $this->ajaxResult['count_signalement_filtered'] = $countSignalementFiltered;
        $this->ajaxResult['average_criticite_filtered'] = $averageCriticiteFiltered;

        $this->ajaxResult['countSignalementPerMonth'] = $countSignalementPerMonth;
        $this->ajaxResult['countSignalementPerPartenaire'] = $countSignalementPerPartenaire;
        $this->ajaxResult['countSignalementPerSituation'] = $countSignalementPerSituation;
        $this->ajaxResult['countSignalementPerCriticite'] = $countSignalementPerCriticite;
        $this->ajaxResult['countSignalementPerStatut'] = $countSignalementPerStatut;
        $this->ajaxResult['countSignalementPerCriticitePercent'] = $countSignalementPerCriticitePercent;
        $this->ajaxResult['countSignalementPerVisite'] = $countSignalementPerVisite;
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
}
