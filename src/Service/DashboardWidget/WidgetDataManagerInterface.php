<?php

namespace App\Service\DashboardWidget;

interface WidgetDataManagerInterface
{
    public function countSignalementAcceptedNoSuivi(array $territories, ?array $params = null): array;

    public function countSignalementsByTerritory(?array $params = null): array;

    public function countAffectationPartner(array $territories, ?array $params = null): array;

    public function findLastJobEventByInterfacageType(string $type, array $params, array $territories): array;

    public function countDataKpi(array $territories, ?array $params = null): WidgetDataKpi;
}
