<?php

namespace App\Service\DashboardTabPanel\Kpi;

use App\Entity\User;
use App\Service\DashboardTabPanel\TabQueryParameters;

interface TabCountKpiCalculatorInterface
{
    /**
     * @param array<int, mixed> $territories
     */
    public function countNouveauxDossiers(array $territories, User $user): CountNouveauxDossiers;

    public function countDossiersAFermer(User $user, TabQueryParameters $params): CountAfermer;

    public function countDossiersMessagesUsagers(User $user, TabQueryParameters $params): CountDossiersMessagesUsagers;

    public function countDossiersAVerifier(User $user, TabQueryParameters $params): CountDossiersAVerifier;
}
