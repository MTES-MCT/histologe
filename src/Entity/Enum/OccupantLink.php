<?php

namespace App\Entity\Enum;

enum OccupantLink: string
{
    case PROCHE = 'PROCHE';
    case VOISINAGE = 'VOISINAGE';
    case AUTRE = 'AUTRE';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    public static function getLabelList(): array
    {
        return [
            'PROCHE' => 'PROCHE',
            'VOISINAGE' => 'VOISIN', /* legacy label */
            'AUTRE' => 'AUTRE',
        ];
    }
}
