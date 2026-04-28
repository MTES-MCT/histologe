<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum ProprioType: string
{
    use EnumTrait;
    case PARTICULIER = 'PARTICULIER';
    case ORGANISME_SOCIETE = 'ORGANISME_SOCIETE';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            'PARTICULIER' => 'Particulier',
            'ORGANISME_SOCIETE' => 'Organisme / Société',
        ];
    }
}
