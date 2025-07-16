<?php

namespace App\Service\DashboardTabPanel;

class TabCountKpiFactory
{
    public function createInstance(): TabCountKpi
    {
        return new TabCountKpi(
            countNouveauxDossiers: rand(10, 30),
            countDossiersAFermer: rand(80, 100),
            countDossiersMessagesUsagers: rand(200, 300),
            countDossiersAVerifier: rand(500, 1000)
        );
    }
}
