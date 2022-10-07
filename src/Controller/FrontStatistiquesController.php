<?php

namespace App\Controller;

use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Service\Statistics\CountSignalementPerMonthStatisticProvider;
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

    // private const MONTH_NAMES = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

    public function __construct(
        private CountSignalementStatisticProvider $countSignalementStatisticProvider,
        private CountTerritoryStatisticProvider $countTerritoryStatisticProvider,
        private PercentSignalementValidatedStatisticProvider $percentSignalementValidatedStatisticProvider,
        private PercentSignalementClosedStatisticProvider $percentSignalementClosedStatisticProvider,
        private ListTerritoryStatisticProvider $listTerritoryStatisticProvider,
        private CountSignalementPerTerritoryStatisticProvider $countSignalementPerTerritoryStatisticProvider,
        private CountSignalementPerMonthStatisticProvider $countSignalementPerMonthStatisticProvider
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
    public function filter(Request $request, TerritoryRepository $territoryRepository, SignalementRepository $signalementRepository): Response
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

        $this->makeGlobalStats($signalementRepository, $territory);

        return $this->json($this->ajaxResult);
    }

    private function makeGlobalStats(SignalementRepository $signalementRepository, $territory)
    {
        $globalSignalement = $signalementRepository->findByFilters('', true, null, null, '', null, null, null);
        $this->ajaxResult['signalement_per_statut'] = [];
        $this->ajaxResult['signalement_per_statut_this_year'] = [];
        $countSignalementPerSituation = [];
        $countSignalementPerSituationThisYear = [];
        $countSignalementPerMotifCloture = self::initMotifPerValue();
        $countSignalementPerMotifClotureThisYear = self::initMotifPerValue();
        $currentDate = new DateTime();
        $currentYear = $currentDate->format('Y');

        /**
         * @var Signalement $signalementItem
         */
        foreach ($globalSignalement as $signalementItem) {
            $dateCreatedAt = $signalementItem->getCreatedAt();

            // Filter
            if (empty($territory) || 'all' === $territory || $territory === $signalementItem->getTerritory()) {
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

        $this->ajaxResult['signalement_per_situation'] = $countSignalementPerSituation;
        $this->ajaxResult['signalement_per_situation_this_year'] = $countSignalementPerSituationThisYear;
        $this->ajaxResult['signalement_per_motif_cloture'] = $countSignalementPerMotifCloture;
        $this->ajaxResult['signalement_per_motif_cloture_this_year'] = $countSignalementPerMotifClotureThisYear;
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
