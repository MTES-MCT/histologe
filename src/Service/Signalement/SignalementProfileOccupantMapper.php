<?php

namespace App\Service\Signalement;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\ProfileOccupant;

class SignalementProfileOccupantMapper
{
    public static function map(?string $profileOccupant, ?ProfileDeclarant $profileDeclarant): ?ProfileOccupant
    {
        if (ProfileDeclarant::BAILLEUR_OCCUPANT === $profileDeclarant) {
            return ProfileOccupant::BAILLEUR_OCCUPANT;
        } elseif (in_array($profileDeclarant, [ProfileDeclarant::BAILLEUR, ProfileDeclarant::LOCATAIRE])) {
            return ProfileOccupant::LOCATAIRE;
        }

        if (empty($profileOccupant)) {
            return null;
        }

        return ProfileOccupant::tryFrom($profileOccupant);
    }
}
