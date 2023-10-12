<?php

namespace App\Entity\Enum;

use App\Form\SignalementType;

enum OccupantLink: string
{
    case PROCHE = 'PROCHE';
    case VOISINAGE = 'VOISINAGE';
    case AUTRE = 'AUTRE';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    /** @see SignalementType::LINK_CHOICES legacy */
    public static function getLabelList(): array
    {
        return [
            'PROCHE' => 'PROCHE',
            'VOISINAGE' => 'VOISIN',
            'AUTRE' => 'AUTRE',
        ];
    }
}
