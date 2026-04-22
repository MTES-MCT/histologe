<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum ProfileOccupant: string
{
    use EnumTrait;
    case LOCATAIRE = 'LOCATAIRE';
    case BAILLEUR_OCCUPANT = 'BAILLEUR_OCCUPANT';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            'LOCATAIRE' => 'Locataire',
            'BAILLEUR_OCCUPANT' => 'Propriétaire occupant',
        ];
    }
}
