<?php

namespace App\Service\Signalement;

use App\Entity\Signalement;
use App\Entity\Suivi;

class SuiviHelper
{
    public static function getLastLabelFromSuivi(Suivi $suivi): ?string
    {
        return (new self())->getLastLabel($suivi, $suivi->getSignalement());
    }

    public function getLastLabel(Suivi $suivi, Signalement $signalement): string
    {
        $user = $suivi->getCreatedBy();
        if (null === $user && Suivi::TYPE_TECHNICAL === $suivi->getType()) {
            return 'MESSAGE AUTOMATIQUE';
        }
        if (null !== $user && \in_array('ROLE_USAGER', $user->getRoles())) {
            return $user->getEmail() === $signalement->getMailOccupant() ? 'OCCUPANT' : 'DECLARANT';
        }
        if ($suivi->getPartner()) {
            return $suivi->getPartner()->getNom();
        }

        return $user?->getPartnerInTerritory($signalement->getTerritory())?->getNom() ?? 'Aucun';
    }
}
