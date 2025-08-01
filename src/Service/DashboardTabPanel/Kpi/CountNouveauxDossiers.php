<?php

namespace App\Service\DashboardTabPanel\Kpi;

readonly class CountNouveauxDossiers
{
    public function __construct(
        public int $countFormulaireUsager = 0,
        public int $countFormulairePro = 0,
        public int $countSansAffectation = 0,
        public int $countNouveauxDossiers = 0,
    ) {
    }

    public function total(): int
    {
        return $this->countFormulaireUsager
            + $this->countFormulairePro
            + $this->countSansAffectation
            + $this->countNouveauxDossiers;
    }
}
