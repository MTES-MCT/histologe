<?php

namespace App\Service\Signalement;

use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Signalement;
use App\Repository\CritereRepository;

class CriticiteCalculatorService
{
    private Signalement $signalement;
    private int $scoreSignalement;
    private float $scoreBatiment;
    private float $scoreLogement;
    private bool $isDanger;
    private const MAX_SCORE_BATIMENT = 136;
    private const MAX_SCORE_LOGEMENT = 126;

    public function __construct(Signalement $signalement, private CritereRepository $critereRepository)
    {
        $this->signalement = $signalement;
        $this->scoreSignalement = 0;
        $this->scoreBatiment = 0;
        $this->scoreLogement = 0;
        $this->isDanger = false;
    }

    public function calculate(): float|int
    {
        $signalement = $this->signalement;
        $scoreMax = $this->critereRepository->getMaxScore() * Criticite::SCORE_MAX;
        $signalement->getCriticites()->map(function (Criticite $criticite) {
            $this->scoreSignalement += ($criticite->getScore() * $criticite->getCritere()->getCoef());
            if ($criticite->getCritere()->getIsDanger()) {
                $this->isDanger = true;
            }
        });
        $score = ($this->scoreSignalement / $scoreMax) * 1000;
        if ($signalement->getNbEnfantsM6() || $signalement->getNbEnfantsP6()) {
            $score = $score * 1.1;
        }
        if ($this->isDanger) {
            $score = 100;
        }
        if ($score > 100) {
            $score = 100;
        }

        return $score;
    }

    public function calculateNewCriticite(): float|int
    {
        $signalement = $this->signalement;

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
}
