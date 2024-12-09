<?php

namespace App\Service\Signalement;

use App\Entity\Signalement;
use App\Entity\Suivi;

class SuiviHelper
{
    public static function getLastLabelFromSignalement(Signalement $signalement): ?string
    {
        $suivi = $signalement->getLastSuivi();
        if ($suivi instanceof Suivi) {
            return (new self())->getLastLabel($suivi, $signalement);
        }

        return null;
    }

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

        if ($user) {
            foreach ($user->getPartners() as $partner) {
                if ($signalement->getTerritory() === $partner->getTerritory()) {
                    return $partner->getNom();
                }
            }
            if ($user->getPartners()->count()) {
                return $user->getPartners()->first()->getNom();
            }
        }

        return 'Aucun';
    }
}
