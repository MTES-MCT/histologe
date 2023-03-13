<?php

namespace App\Service\Signalement;

use App\Entity\Signalement;
use App\Entity\Suivi;

class SuiviHelper
{
    public static function getSuiviLastByLabel(Signalement $signalement): ?string
    {
        /** @var Suivi $suivi */
        $suivi = $signalement->getLastSuivi();
        if ($suivi instanceof Suivi) {
            $user = $suivi->getCreatedBy();
            if (\in_array('ROLE_USAGER', $user->getRoles())) {
                return $user->getEmail() === $signalement->getMailOccupant() ? 'OCCUPANT' : 'DECLARANT';
            }

            return $user?->getPartner()?->getNom() ?? 'Aucun';
        }

        return null;

        // return $signalement->getIsNotOccupant() ? 'DECLARANT': 'OCCUPANT';
    }
}
