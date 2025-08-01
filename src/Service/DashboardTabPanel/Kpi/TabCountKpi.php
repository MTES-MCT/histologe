<?php

namespace App\Service\DashboardTabPanel\Kpi;

class TabCountKpi
{
    public function __construct(
        public ?int $countNouveauxDossiers = 0,
        public ?int $countDossiersAFermer = 0,
        public ?int $countDossiersMessagesUsagers = 0,
        public ?int $countDossiersAVerifier = 0,
    ) {
    }
}
