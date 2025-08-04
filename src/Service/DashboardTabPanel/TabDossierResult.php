<?php

namespace App\Service\DashboardTabPanel;

readonly class TabDossierResult
{
    /**
     * @param TabDossier[] $dossiers
     */
    public function __construct(
        public array $dossiers,
        public int $count,
    ) {
    }
}
