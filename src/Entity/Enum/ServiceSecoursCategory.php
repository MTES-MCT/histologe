<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum ServiceSecoursCategory: string
{
    use EnumTrait;

    case SDIS = 'SDIS';
    case POLICE = 'POLICE';
    case GENDARMERIE = 'GENDARMERIE';
    case MARINS_POMPIERS = 'MARINS_POMPIERS';
    case AUTRE = 'AUTRE';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            'SDIS' => 'SDIS',
            'POLICE' => 'Police',
            'GENDARMERIE' => 'Gendarmerie',
            'MARINS_POMPIERS' => 'Marins-pompiers',
            'AUTRE' => 'Autre',
        ];
    }
}
