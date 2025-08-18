<?php

namespace App\Service\DashboardTabPanel\Kpi;

readonly class CountAfermer
{
    public function __construct(
        public int $countDemandesFermetureByUsager = 0,
        public int $countDossiersRelanceSansReponse = 0,
        public int $countDossiersFermePartenaireTous = 0,
    ) {
    }

    public function total(): int
    {
        return $this->countDemandesFermetureByUsager
            + $this->countDossiersRelanceSansReponse
            + $this->countDossiersFermePartenaireTous;
    }
}
