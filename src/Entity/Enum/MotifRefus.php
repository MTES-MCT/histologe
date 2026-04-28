<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum MotifRefus: string
{
    use EnumTrait;
    case HORS_PDLHI = 'HORS_PDLHI';
    case HORS_ZONE_GEOGRAPHIQUE = 'HORS_ZONE_GEOGRAPHIQUE';
    case HORS_COMPETENCE = 'HORS_COMPETENCE';
    case DOUBLON = 'DOUBLON';
    case AUTRE = 'AUTRE';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            'HORS_PDLHI' => 'Hors PDLHI',
            'HORS_ZONE_GEOGRAPHIQUE' => 'Hors zone géographique',
            'HORS_COMPETENCE' => 'Hors compétence',
            'DOUBLON' => 'Doublon',
            'AUTRE' => 'Autre',
        ];
    }
}
