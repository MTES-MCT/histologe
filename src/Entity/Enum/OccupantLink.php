<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum OccupantLink: string
{
    use EnumTrait;

    case PROCHE = 'PROCHE';
    case VOISIN = 'VOISIN';
    case SECOURS = 'SECOURS';
    case BAILLEUR = 'BAILLEUR';
    case PRO = 'PRO';
    case AUTRE = 'AUTRE';

    public static function getLabelList(): array
    {
        return [
            self::PROCHE->name => 'Proche',
            self::VOISIN->name => 'Voisin',
            self::SECOURS->name => 'Services de secours',
            self::BAILLEUR->name => 'Bailleur',
            self::PRO->name => 'Pro',
            self::AUTRE->name => 'Autre',
        ];
    }
}
