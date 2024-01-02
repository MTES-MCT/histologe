<?php

namespace App\Service\Signalement;

use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Enum\DesordreCritereZone;
use App\Entity\Signalement;

class CriticiteCalculator
{
    private float $scoreBatiment;
    private float $scoreLogement;
    private const MAX_SCORE_BATIMENT = 136;
    private const MAX_SCORE_LOGEMENT = 126;
    private const MAX_NEW_SCORE_BATIMENT = 80;
    private const MAX_NEW_SCORE_LOGEMENT = 102;

    public function calculate(Signalement $signalement): float|int
    {
        $this->scoreBatiment = 0;
        $this->scoreLogement = 0;
        $signalement->getCriticites()->map(function (Criticite $criticite) {
            if (Critere::TYPE_BATIMENT === $criticite->getCritere()->getType()) {
                $this->scoreBatiment += ($criticite->getNewScore() * $criticite->getCritere()->getNewCoef());
            }
            if (Critere::TYPE_LOGEMENT === $criticite->getCritere()->getType()) {
                $this->scoreLogement += ($criticite->getNewScore() * $criticite->getCritere()->getNewCoef());
            }
        });

        $scoreBatiment = ($this->scoreBatiment / self::MAX_SCORE_BATIMENT) * 100;
        $scoreLogement = ($this->scoreLogement / self::MAX_SCORE_LOGEMENT) * 100;

        $score = ($scoreBatiment + $scoreLogement) / 2;

        if ($signalement->getNbEnfantsM6() || $signalement->getNbEnfantsP6()) {
            $score = $score * 1.1;
        }
        if ($score > 100) {
            $score = 100;
        }

        return $score;
    }

    public function calculateFromNewFormulaire(Signalement $signalement): float|int
    {
        $this->scoreBatiment = 0;
        $this->scoreLogement = 0;
        foreach ($signalement->getDesordrePrecisions() as $desordrePrecision) {
            if (DesordreCritereZone::BATIMENT === $desordrePrecision->getDesordreCritere()->getZoneCategorie()) {
                $this->scoreBatiment += $desordrePrecision->getCoef();
            }
            if (DesordreCritereZone::LOGEMENT === $desordrePrecision->getDesordreCritere()->getZoneCategorie()) {
                $this->scoreLogement += $desordrePrecision->getCoef();
            }
        }

        $scoreBatiment = ($this->scoreBatiment / self::MAX_NEW_SCORE_BATIMENT) * 100;
        $scoreLogement = ($this->scoreLogement / self::MAX_NEW_SCORE_LOGEMENT) * 100;

        $signalement->setScoreBatiment($scoreBatiment);
        $signalement->setScoreLogement($scoreLogement);

        $score = ($scoreBatiment + $scoreLogement) / 2;

        if ('oui' === $signalement->getTypeCompositionLogement()->getCompositionLogementEnfants()) {
            $score = $score * 1.1;
        }
        if ($score > 100) {
            $score = 100;
        }

        return $score;
    }
}
