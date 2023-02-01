<?php

namespace App\Service\DashboardWidget;

use App\Entity\Territory;

interface WidgetDataManagerInterface
{
    public function countSignalementAcceptedNoSuivi(Territory $territory): array;

    public function countSignalementsByTerritory(): array;

    public function countAffectationPartner(?Territory $territory = null): array;

    public function findLastJobEventByType(string $type): array;

    public function countDataKpi(?Territory $territory = null): WidgetDataKpi;
}
