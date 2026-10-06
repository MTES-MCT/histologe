<?php

namespace App\Service\Metabase;

enum DashboardKey: int
{
    public const int DASHBOARD_BO_DEFAULT_TAB = 33;

    case DASHBOARD_BO = 30;

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
