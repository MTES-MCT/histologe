<?php

namespace App\Service\DashboardWidget;

/**
 * @deprecated This class will be removed once the FEATURE_NEW_DASHBOARD feature flag is removed.
 * Please refer to the `App\Service\DashboardTabPanel` namespace for the new dashboard.
 */
interface WidgetDataManagerInterface
{
    /**
     * @param array<int, mixed>         $territories
     * @param array<string, mixed>|null $params
     *
     * @return array<mixed>
     */
    public function countSignalementAcceptedNoSuivi(array $territories, ?array $params = null): array;

    /**
     * @param array<string, mixed>|null $params
     *
     * @return array<mixed>
     */
    public function countSignalementsByTerritory(?array $params = null): array;

    /**
     * @param array<int, mixed>         $territories
     * @param array<string, mixed>|null $params
     *
     * @return array<mixed>
     */
    public function countAffectationPartner(array $territories, ?array $params = null): array;

    /**
     * @param array<string, mixed> $params
     * @param array<int, mixed>    $territories
     *
     * @return array<mixed>
     */
    public function findLastJobEventByInterfacageType(string $type, array $params, array $territories): array;

    /**
     * @param array<int, mixed>         $territories
     * @param array<string, mixed>|null $params
     */
    public function countDataKpi(array $territories, ?array $params = null): WidgetDataKpi;
}
