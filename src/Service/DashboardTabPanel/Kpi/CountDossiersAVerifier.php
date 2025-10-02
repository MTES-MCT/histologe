<?php

namespace App\Service\DashboardTabPanel\Kpi;

readonly class CountDossiersAVerifier
{
    public function __construct(
        public int $countSignalementsSansSuiviPartenaireDepuis60Jours = 0,
        public int $countAdresseEmailAVerifier = 0,
    ) {
    }

    public function total(): int
    {
        return $this->countSignalementsSansSuiviPartenaireDepuis60Jours
            + $this->countAdresseEmailAVerifier;
    }
}
