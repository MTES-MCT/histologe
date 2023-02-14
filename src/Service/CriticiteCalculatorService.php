<?php

namespace App\Service;

use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Signalement;
use App\Repository\CritereRepository;
use Doctrine\Persistence\ManagerRegistry;

class CriticiteCalculatorService
{
    private Signalement $signalement;
    private ManagerRegistry $doctrine;
    private CritereRepository $critereRepository;
    private int $scoreSignalement;
    private float $scoreBatiment;
    private float $scoreLogement;
    private float $newScoreSignalement;
    private bool $isDanger;

    public function __construct(Signalement $signalement, ManagerRegistry $doctrine)
    {
        $this->signalement = $signalement;
        $this->doctrine = $doctrine;
        $this->scoreSignalement = 0;
        $this->newScoreSignalement = 0;
        $this->scoreBatiment = 0;
        $this->scoreLogement = 0;
        $this->isDanger = false;
        $this->critereRepository = $this->doctrine->getRepository(Critere::class);
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
        $scoreMaxBatiment = $this->critereRepository->getMaxNewScore(Critere::TYPE_BATIMENT);
        $scoreMaxLogement = $this->critereRepository->getMaxNewScore(Critere::TYPE_LOGEMENT);

        $signalement->getCriticites()->map(function (Criticite $criticite) {
            if (Critere::TYPE_BATIMENT === $criticite->getCritere()->getType()) {
                $this->scoreBatiment += ($criticite->getNewScore() * $criticite->getCritere()->getNewCoef());
            }
            if (Critere::TYPE_LOGEMENT === $criticite->getCritere()->getType()) {
                $this->scoreLogement += ($criticite->getNewScore() * $criticite->getCritere()->getNewCoef());
            }
        });

        $scoreBatiment = ($this->scoreBatiment / $scoreMaxBatiment) * 100;
        $scoreLogement = ($this->scoreLogement / $scoreMaxLogement) * 100;

        $score = ($scoreBatiment + $scoreLogement) / 2;

        if ($signalement->getNbEnfantsM6() || $signalement->getNbEnfantsP6()) {// enfant de plus de 6 ans aussi ?
            $score = $score * 1.1;
        }
        if ($score > 100) {
            $score = 100;
        }

        return $score;
    }
}
