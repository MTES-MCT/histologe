<?php

namespace App\Controller;

use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use DateTime;
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
        $title = 'En quelques chiffres';

        return $this->render('front/statistiques.html.twig', [
            'title' => $title,
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

        $territory = null;
        $request_territoire = $request->get('territoire');
        if ('' !== $request_territoire && 'all' !== $request_territoire) {
            $territory = $territoryRepository->findOneBy(['id' => $request_territoire]);
        }

        $this->makeGlobalStats($signalementRepository, $territoryRepository, $territoryList, $territory);

        $this->ajaxResult['response'] = 'success';

        return $this->json($this->ajaxResult);
    }

    private function makeGlobalStats(SignalementRepository $signalementRepository, TerritoryRepository $territoryRepository, $territoryList, $territory)
    {
        $globalSignalement = $signalementRepository->findByFilters('', true, null, null, '', null, null, null);
        $totalSignalement = \count($globalSignalement);
        $this->ajaxResult['count_signalement'] = $totalSignalement;
        $this->ajaxResult['count_territory'] = \count($territoryList);
        $this->ajaxResult['signalement_per_territoire'] = [];
        $listMonthName = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        $countSignalementPerMonth = [];
        $countSignalementPerMonthThisYear = [];
        $this->ajaxResult['signalement_per_statut'] = [];
        $this->ajaxResult['signalement_per_statut_this_year'] = [];
        $countSignalementPerSituation = [];
        $countSignalementPerSituationThisYear = [];
        $countSignalementPerMotifCloture = self::initMotifPerValue();
        $countSignalementPerMotifClotureThisYear = self::initMotifPerValue();
        $currentDate = new DateTime();
        $currentYear = $currentDate->format('Y');

        $totalValidation = 0;
        $totalCloture = 0;
        /**
         * @var Signalement $signalementItem
         */
        foreach ($globalSignalement as $signalementItem) {
            $dateCreatedAt = $signalementItem->getCreatedAt();

            if (Signalement::STATUS_NEED_VALIDATION !== $signalementItem->getStatut() && Signalement::STATUS_REFUSED !== $signalementItem->getStatut()) {
                ++$totalValidation;
            }
            if (Signalement::STATUS_CLOSED === $signalementItem->getStatut()) {
                ++$totalCloture;
            }

            // Per territoire
            $territoryId = $signalementItem->getTerritory()->getId();
            if (empty($this->ajaxResult['signalement_per_territoire'][$territoryId])) {
                $this->ajaxResult['signalement_per_territoire'][$territoryId] = [
                    'name' => $signalementItem->getTerritory()->getName(),
                    'zip' => $signalementItem->getTerritory()->getZip(),
                    'count' => 0,
                ];
            }
            ++$this->ajaxResult['signalement_per_territoire'][$territoryId]['count'];

            // Filter
            if (empty($territory) || 'all' === $territory || $territory === $signalementItem->getTerritory()) {
                // Per month
                if (empty($countSignalementPerMonth[$dateCreatedAt->format('Y-m')])) {
                    $countSignalementPerMonth[$dateCreatedAt->format('Y-m')] = 0;
                }
                ++$countSignalementPerMonth[$dateCreatedAt->format('Y-m')];

                // Per statut
                $statut = $signalementItem->getStatut();
                if (empty($this->ajaxResult['signalement_per_statut'][$statut])) {
                    $newStatutValues = self::initStatutByValue($statut);
                    if (!empty($newStatutValues)) {
                        $this->ajaxResult['signalement_per_statut'][$statut] = $newStatutValues;
                    }
                }
                if (!empty($this->ajaxResult['signalement_per_statut'][$statut])) {
                    ++$this->ajaxResult['signalement_per_statut'][$statut]['count'];
                }

                // Per situation
                $listSituations = $signalementItem->getSituations();
                $countListSituations = \count($listSituations);
                for ($i = 0; $i < $countListSituations; ++$i) {
                    $situationStr = $listSituations[$i]->getMenuLabel();
                    if (empty($countSignalementPerSituation[$situationStr])) {
                        $countSignalementPerSituation[$situationStr] = 0;
                    }
                    ++$countSignalementPerSituation[$situationStr];
                }

                // Per motif
                $dateClosedAt = $signalementItem->getClosedAt();
                $motifCloture = $signalementItem->getMotifCloture();
                if (null !== $dateClosedAt && !empty($motifCloture) && !empty($countSignalementPerMotifCloture[$motifCloture])) {
                    ++$countSignalementPerMotifCloture[$motifCloture]['count'];
                }

                // This year
                if ($dateCreatedAt->format('Y') == $currentYear) {
                    // Per month
                    if (empty($countSignalementPerMonthThisYear[$dateCreatedAt->format('Y-m')])) {
                        $countSignalementPerMonthThisYear[$dateCreatedAt->format('Y-m')] = 0;
                    }
                    ++$countSignalementPerMonthThisYear[$dateCreatedAt->format('Y-m')];

                    // Per statut
                    if (empty($this->ajaxResult['signalement_per_statut_this_year'][$statut])) {
                        $newStatutValues = self::initStatutByValue($statut);
                        if (!empty($newStatutValues)) {
                            $this->ajaxResult['signalement_per_statut_this_year'][$statut] = $newStatutValues;
                        }
                    }
                    if (!empty($this->ajaxResult['signalement_per_statut_this_year'][$statut])) {
                        ++$this->ajaxResult['signalement_per_statut_this_year'][$statut]['count'];
                    }

                    // Per situation
                    $countListSituations = \count($listSituations);
                    for ($i = 0; $i < $countListSituations; ++$i) {
                        $situationStr = $listSituations[$i]->getMenuLabel();
                        if (empty($countSignalementPerSituationThisYear[$situationStr])) {
                            $countSignalementPerSituationThisYear[$situationStr] = 0;
                        }
                        ++$countSignalementPerSituationThisYear[$situationStr];
                    }

                    // Per motif
                    if (null !== $dateClosedAt && !empty($motifCloture) && !empty($countSignalementPerMotifClotureThisYear[$motifCloture])) {
                        ++$countSignalementPerMotifClotureThisYear[$motifCloture]['count'];
                    }
                }
            }
        }

        $percentValidation = $totalSignalement > 0 ? round($totalValidation / $totalSignalement * 1000) / 10 : '-';
        $percentCloture = $totalSignalement > 0 ? round($totalCloture / $totalSignalement * 1000) / 10 : '-';
        $this->ajaxResult['percent_validation'] = $percentValidation;
        $this->ajaxResult['percent_cloture'] = $percentCloture;
        $this->ajaxResult['signalement_per_situation'] = $countSignalementPerSituation;
        $this->ajaxResult['signalement_per_situation_this_year'] = $countSignalementPerSituationThisYear;
        $this->ajaxResult['signalement_per_motif_cloture'] = $countSignalementPerMotifCloture;
        $this->ajaxResult['signalement_per_motif_cloture_this_year'] = $countSignalementPerMotifClotureThisYear;

        ksort($countSignalementPerMonth);
        $this->ajaxResult['signalement_per_month'] = [];
        foreach ($countSignalementPerMonth as $month => $count) {
            $dateMonth = new DateTime($month);
            $strMonth = $listMonthName[$dateMonth->format('m') - 1].' '.$dateMonth->format('Y');
            $this->ajaxResult['signalement_per_month'][$strMonth] = $count;
        }
        ksort($countSignalementPerMonthThisYear);
        $this->ajaxResult['signalement_per_month_this_year'] = [];
        foreach ($countSignalementPerMonthThisYear as $month => $count) {
            $dateMonth = new DateTime($month);
            $strMonth = $listMonthName[$dateMonth->format('m') - 1].' '.$dateMonth->format('Y');
            $this->ajaxResult['signalement_per_month_this_year'][$strMonth] = $count;
        }
    }

    /**
     * Init list of Signalement by Statut, to retrieve label and color.
     */
    private static function initStatutByValue($statut)
    {
        switch ($statut) {
            case Signalement::STATUS_CLOSED:
                return [
                    'label' => 'Fermé',
                    'color' => '#21AB8E',
                    'count' => 0,
                ];
                break;

            case Signalement::STATUS_ACTIVE:
            case Signalement::STATUS_NEED_PARTNER_RESPONSE:
                return [
                    'label' => 'En cours',
                    'color' => '#000091',
                    'count' => 0,
                ];
                break;

            case Signalement::STATUS_NEED_VALIDATION:
                return [
                    'label' => 'Nouveau',
                    'color' => '#E4794A',
                    'count' => 0,
                ];
                break;

            default:
                return false;
                break;
        }
    }

    private static function initMotifPerValue()
    {
        // TODO : finaliser les couleurs
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
