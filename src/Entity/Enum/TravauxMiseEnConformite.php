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

    public function labelForUsager(): string
    {
        $labels = [
            self::OUI->value => 'Les travaux ont été faits',
            self::NON->value => 'Il n\'y a pas eu de travaux',
            self::EN_COURS->value => 'Les travaux sont en cours',
        ];

        return $labels[$this->name];
    }
}
