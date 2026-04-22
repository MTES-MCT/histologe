<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum ProfileDeclarant: string
{
    use EnumTrait;
    case LOCATAIRE = 'LOCATAIRE';
    case BAILLEUR_OCCUPANT = 'BAILLEUR_OCCUPANT';
    case TIERS_PARTICULIER = 'TIERS_PARTICULIER';
    case TIERS_PRO = 'TIERS_PRO';
    case SERVICE_SECOURS = 'SERVICE_SECOURS';
    case BAILLEUR = 'BAILLEUR';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            'LOCATAIRE' => 'Locataire',
            'BAILLEUR_OCCUPANT' => 'Propriétaire occupant',
            'TIERS_PARTICULIER' => 'Tiers particulier',
            'TIERS_PRO' => 'Tiers professionnel',
            'SERVICE_SECOURS' => 'Service de secours',
            'BAILLEUR' => 'Bailleur',
        ];
    }

    /** @return array<string> */
    public static function getListWithGroup(): array
    {
        return array_merge(array_column(self::cases(), 'name'), ['all', 'tiers', 'occupant']);
    }
}
