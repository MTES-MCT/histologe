<?php

namespace App\Service;

use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Signalement;
use Doctrine\Persistence\ManagerRegistry;

class CriticiteCalculatorService
{
    private Signalement $signalement;
    private ManagerRegistry $doctrine;
    private int $scoreSignalement;
    private bool $isDanger;

    public function __construct(Signalement $signalement, ManagerRegistry $doctrine)
    {
        $this->signalement = $signalement;
        $this->doctrine = $doctrine;
        $this->scoreSignalement = 0;
        $this->isDanger = false;
    }

    public function calculate(): float|int
    {
        $signalement = $this->signalement;
        $scoreMax = $this->doctrine->getRepository(Critere::class)->getMaxScore() * Criticite::SCORE_MAX;
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
}
