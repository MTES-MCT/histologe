<?php

namespace App\Service\DashboardTabPanel;

class TabCacheCommonKeyGenerator
{
    public function generate(): string
    {
        // Génère une clé commune pour le cache, à adapter selon le besoin métier
        return 'tab-common-key';
    }
}
