<?php

namespace App\Service\Statistics;

use App\Entity\Territory;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use DateTime;
use Symfony\Component\HttpFoundation\Request;

class FilteredBackAnalyticsProvider
{
    private $communes;
    private $statut;
    private $etiquettes;
    private $type;
    private $dateStart;
    private $dateEnd;
    private $hasCountRefused;
    private $territory;

    public function __construct(
        private SignalementRepository $signalementRepository,
        private TerritoryRepository $territoryRepository,
        private MonthStatisticProvider $monthStatisticProvider,
        private PartenaireStatisticProvider $partenaireStatisticProvider,
        private SituationStatisticProvider $situationStatisticProvider,
        private CriticiteStatisticProvider $criticiteStatisticProvider,
        ) {
    }

    public function initFilters(Request $request, ?Territory $territory)
    {
        $this->communes = json_decode($request->get('communes'));
        $this->statut = $request->get('statut');
        $strEtiquettes = $request->get('etiquettes');
        $this->etiquettes = array_map(fn ($value): int => $value * 1, json_decode($strEtiquettes));
        $this->type = $request->get('type');
        $dateStart = $request->get('dateStart');
        $this->dateStart = new DateTime($dateStart);
        $dateEnd = $request->get('dateEnd');
        $this->dateEnd = new DateTime($dateEnd);
        $this->hasCountRefused = '1' == $request->get('countRefused');
        $this->territory = $territory;
    }

    public function getData()
    {
        $buffer = [];
        $buffer['count_signalement_filtered'] = $this->getCountSignalementData();
        $buffer['average_criticite_filtered'] = round($this->getAverageCriticiteData() * 10) / 10;
        $buffer['count_signalement_per_month'] = $this->getCountSignalementPerMonth();
        $buffer['count_signalement_per_partenaire'] = $this->getCountSignalementPerPartenaire();
        $buffer['count_signalement_per_situation'] = $this->getCountSignalementPerSituation();
        $buffer['count_signalement_per_criticite'] = $this->getCountSignalementPerCriticite();

        return $buffer;
    }

    private function getCountSignalementData()
    {
        return $this->signalementRepository->countFiltered($this->statut, $this->hasCountRefused, $this->dateStart, $this->dateEnd, $this->type, $this->territory, $this->etiquettes, $this->communes);
    }

    private function getAverageCriticiteData()
    {
        $buffer = $this->signalementRepository->getAverageCriticiteFiltered($this->statut, $this->hasCountRefused, $this->dateStart, $this->dateEnd, $this->type, $this->territory, $this->etiquettes, $this->communes);
        if (empty($buffer)) {
            $buffer = 0;
        }

        return $buffer;
    }

    private function getCountSignalementPerMonth()
    {
        return $this->monthStatisticProvider->getFilteredData($this->statut, $this->hasCountRefused, $this->dateStart, $this->dateEnd, $this->type, $this->territory, $this->etiquettes, $this->communes);
    }

    private function getCountSignalementPerPartenaire()
    {
        return $this->partenaireStatisticProvider->getFilteredData($this->statut, $this->hasCountRefused, $this->dateStart, $this->dateEnd, $this->type, $this->territory, $this->etiquettes, $this->communes);
    }

    private function getCountSignalementPerSituation()
    {
        return $this->situationStatisticProvider->getFilteredData($this->statut, $this->hasCountRefused, $this->dateStart, $this->dateEnd, $this->type, $this->territory, $this->etiquettes, $this->communes);
    }

    private function getCountSignalementPerCriticite()
    {
        return $this->criticiteStatisticProvider->getFilteredData($this->statut, $this->hasCountRefused, $this->dateStart, $this->dateEnd, $this->type, $this->territory, $this->etiquettes, $this->communes);
    }
}
