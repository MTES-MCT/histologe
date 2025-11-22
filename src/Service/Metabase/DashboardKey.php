<?php

namespace App\Service\Metabase;

enum DashboardKey: int
{
    public const int DASHBOARD_BO_DEFAULT_TAB = 22;

    case DASHBOARD_BO = 97;

    public function label(): string
    {
        return match ($this) {
            self::DASHBOARD_BO => 'Dashboard BO',
        };
    }

    public function getDefaultTab(): int
    {
        return match ($this) {
            self::DASHBOARD_BO => self::DASHBOARD_BO_DEFAULT_TAB,
        };
    }
}
