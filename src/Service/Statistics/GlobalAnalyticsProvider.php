<?php

namespace App\Service\Statistics;

use App\Entity\Enum\MotifCloture;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;

class GlobalAnalyticsProvider
{
    public function __construct(
        private SignalementRepository $signalementRepository,
        private TerritoryRepository $territoryRepository
    ) {
    }

    public function getData(): array
    {
        $data = [];
        $data['count_signalement_resolus'] = $this->getCountSignalementResoluData();
        $data['count_signalement'] = $this->getCountSignalementData();
        $data['count_territory'] = $this->getCountTerritoryData();
        $data['percent_validation'] = $this->getValidatedData();
        $data['percent_cloture'] = $this->getClotureData();
        $data['count_imported'] = $this->getImportedData();

        return $data;
    }

    public function getCountSignalementResoluData(): int
    {
        $countPerMotifsCloture = $this->signalementRepository->countByMotifCloture(
            territory: null,
            year: null,
            removeImported: true
        );
        $countSignalementsResolus = 0;
        foreach ($countPerMotifsCloture as $countPerMotifCloture) {
            if ((MotifCloture::TRAVAUX_FAITS_OU_EN_COURS->value == $countPerMotifCloture['motifCloture']->value
                || MotifCloture::RELOGEMENT_OCCUPANT->value == $countPerMotifCloture['motifCloture']->value
                || MotifCloture::INSALUBRITE->value == $countPerMotifCloture['motifCloture']->value
                || MotifCloture::RSD->value == $countPerMotifCloture['motifCloture']->value
                || MotifCloture::PERIL->value == $countPerMotifCloture['motifCloture']->value)
                && !empty($countPerMotifCloture['count'])
            ) {
                $countSignalementsResolus += $countPerMotifCloture['count'];
            }
        }

        return $countSignalementsResolus;
    }

    public function getCountSignalementData(): int
    {
        return $this->signalementRepository->countAll(
            territory: null,
            partner: null,
            removeImported: true,
            removeArchived: true,
            removeRefused: true
        );
    }

    public function getCountTerritoryData(): int
    {
        return $this->territoryRepository->countAll();
    }

    private function getValidatedData(): string|float
    {
        $total = $this->getCountSignalementData();
        if ($total > 0) {
            return round($this->signalementRepository->countValidated(true) / $total * 1000) / 10;
        }

        return '-';
    }

    private function getClotureData(): string|float
    {
        $total = $this->getCountSignalementData();
        if ($total > 0) {
            return round($this->signalementRepository->countClosed(true) / $total * 1000) / 10;
        }

        return '-';
    }

    private function getImportedData(): int
    {
        return $this->signalementRepository->countImported();
    }
}
