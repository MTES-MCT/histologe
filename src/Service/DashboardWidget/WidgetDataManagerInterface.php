<?php

namespace App\Service\DashboardWidget;

use App\Entity\Territory;

interface WidgetDataManagerInterface
{
    public function countSignalementAcceptedNoSuivi(Territory $territory, ?array $params = null): array;

    public function countSignalementsByTerritory(?array $params = null): array;

    public function countAffectationPartner(?Territory $territory = null, ?array $params = null): array;

    public function findLastJobEventByInterfacageType(string $type, array $params, ?Territory $territory = null): array;

    public function countDataKpi(?Territory $territory = null, ?array $params = null): WidgetDataKpi;
}
