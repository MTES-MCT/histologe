<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum DesordreCritereZone: string
{
    use EnumTrait;
    case BATIMENT = 'BATIMENT';
    case LOGEMENT = 'LOGEMENT';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            'BATIMENT' => 'Bâtiment',
            'LOGEMENT' => 'Logement',
        ];
    }
}
