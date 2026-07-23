<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum TravauxMiseEnConformite: string
{
    use EnumTrait;

    case OUI = 'OUI';
    case NON = 'NON';
    case EN_COURS = 'EN_COURS';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            self::OUI->value => 'Oui',
            self::NON->value => 'Non',
            self::EN_COURS->value => 'Ils sont en cours',
        ];
    }
}
