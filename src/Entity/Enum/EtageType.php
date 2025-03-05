<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum EtageType: string
{
    use EnumTrait;

    case RDC = 'RDC';
    case DERNIER_ETAGE = 'DERNIER_ETAGE';
    case SOUSSOL = 'SOUSSOL';
    case AUTRE = 'AUTRE';

    public static function getLabelList(): array
    {
        return [
            'RDC' => 'Rez-de-chaussée',
            'DERNIER_ETAGE' => 'Dernier étage',
            'SOUSSOL' => 'Sous-sol',
            'AUTRE' => 'Autre étage',
        ];
    }
}
