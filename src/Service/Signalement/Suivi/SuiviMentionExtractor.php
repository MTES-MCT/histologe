<?php

namespace App\Service\Signalement\Suivi;

use App\Entity\Suivi;

readonly class SuiviMentionExtractor
{
    /**
     * @return array<int, int>
     */
    public function extract(Suivi $suivi): array
    {
        if ($suivi->getIsVisibleForUsager() || $suivi->getIsVisibleForBailleur()) {
            return []; // pas de notification de mention si le suivi est visible usager/bailleur
        }

        preg_match_all('/data-partner-id="(\d+)"/', $suivi->getDescription(raw: true), $matches);

        return array_unique(array_map('intval', $matches[1]));
    }
}
