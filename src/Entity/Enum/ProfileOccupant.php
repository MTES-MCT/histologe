<?php

namespace App\Entity\Enum;

enum ProfileOccupant: string
{
    case LOCATAIRE = 'LOCATAIRE';
    case BAILLEUR_OCCUPANT = 'BAILLEUR_OCCUPANT';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            'LOCATAIRE' => 'Locataire',
            'BAILLEUR_OCCUPANT' => 'Propriétaire occupant',
        ];
    }
}
