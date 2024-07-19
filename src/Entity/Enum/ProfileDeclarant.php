<?php

namespace App\Entity\Enum;

enum ProfileDeclarant: string
{
    case LOCATAIRE = 'LOCATAIRE';
    case BAILLEUR_OCCUPANT = 'BAILLEUR_OCCUPANT';
    case TIERS_PARTICULIER = 'TIERS_PARTICULIER';
    case TIERS_PRO = 'TIERS_PRO';
    case SERVICE_SECOURS = 'SERVICE_SECOURS';
    case BAILLEUR = 'BAILLEUR';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

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

    public static function getListWithGroup(): array
    {
        return array_merge(array_column(self::cases(), 'name'), ['all', 'tiers', 'occupant']);
    }
}
