<?php

namespace App\Service\Signalement;

use App\Entity\Enum\DesordreCritereZone;
use App\Entity\Signalement;

class SignalementDesordresProcessor
{
    public function __construct(
    ) {
    }

    public function process(
        Signalement $signalement,
    ): array {
        $isDanger = false;
        $criticitesArranged = [];
        $photos = [];
        $criteres = [];
        if (!$signalement->isV2()) {
            foreach ($signalement->getCriticites() as $criticite) {
                $situationLabel = $criticite->getCritere()->getSituation()->getLabel();
                $critereLabel = $criticite->getCritere()->getLabel();
                $criticitesArranged[$situationLabel][$critereLabel] = $criticite;
                if ($criticite->getIsDanger()) {
                    $isDanger = true;
                }
            }
        } else {
            foreach ($signalement->getDesordrePrecisions() as $desordrePrecision) {
                $zone = $desordrePrecision->getDesordreCritere()->getZoneCategorie();
                $labelCategorieBO = $desordrePrecision->getDesordreCritere()->getDesordreCategorie()->getLabel();
                $labelCritere = $desordrePrecision->getDesordreCritere()->getLabelCritere();
                $criticitesArranged[$zone->value][$labelCategorieBO][$labelCritere][] = $desordrePrecision;

                // ajoute les photos liées au critère et à la précision
                $desordrePrecisionSlug = $desordrePrecision->getDesordrePrecisionSlug();
                $desordreCritereSlug = $desordrePrecision->getDesordreCritere()->getSlugCritere();
                $desordreCategorieSlug = $desordrePrecision->getDesordreCritere()->getSlugCategorie();

                $this->addPhotoBySlug($photos, $signalement, $desordreCritereSlug, $desordrePrecisionSlug);
                $this->addPhotoBySlug($photos, $signalement, $desordreCritereSlug, $desordreCritereSlug);
                $this->addPhotoBySlug($photos, $signalement, $labelCategorieBO, $desordreCategorieSlug);

                $criteres[$desordreCritereSlug] = $labelCritere;
            }
        }

        return [
            'criticitesArranged' => $criticitesArranged,
            'photos' => $photos,
            'isDanger' => $isDanger,
            'criteres' => $criteres,
        ];
    }

    private function addPhotoBySlug(
        array &$photos,
        Signalement $signalement,
        string $key,
        string $slug,
    ): void {
        if (!isset($photos[$key])) {
            $photos[$key] = [];
        }

        $photos[$key] = array_unique(
            array_merge($photos[$key], PhotoHelper::getPhotosBySlug($signalement, $slug)),
            \SORT_REGULAR
        );
    }

    public function processDesordresByZone(
        Signalement $signalement,
    ): array {
        $criteres = [];
        $criteres[DesordreCritereZone::BATIMENT->value] = [];
        $criteres[DesordreCritereZone::LOGEMENT->value] = [];
        foreach ($signalement->getDesordreCriteres() as $desordreCritere) {
            $zone = $desordreCritere->getZoneCategorie();
            $desordrePrecisionsSelected = $signalement->getDesordrePrecisions()->filter(function ($desordrePrecision) use ($desordreCritere) {
                return $desordrePrecision->getDesordreCritere() == $desordreCritere;
            });
            $desordrePrecisionsLinked = $desordreCritere->getDesordrePrecisions();
            $criteres[$zone->value][] = [
                'desordreCritere' => $desordreCritere,
                'desordrePrecisionsSelected' => $desordrePrecisionsSelected,
                'desordrePrecisionsLinked' => $desordrePrecisionsLinked,
            ];
        }

        return $criteres;
    }
}
