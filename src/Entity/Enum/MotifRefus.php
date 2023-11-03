<?php

namespace App\Entity\Enum;

enum MotifRefus: string
{
    case HORS_PDLHI = 'HORS_PDLHI';
    case HORS_ZONE_GEOGRAPHIQUE = 'HORS_ZONE_GEOGRAPHIQUE';
    case HORS_COMPETENCE = 'HORS_COMPETENCE';
    case DOUBLON = 'DOUBLON';
    case AUTRE = 'AUTRE';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

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
