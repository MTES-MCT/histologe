<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum TravauxMiseEnConformite: string
{
    use EnumTrait;

    case OUI = 'OUI';
    case NON = 'NON';
    case EN_COURS = 'EN_COURS';
    case NE_SAIT_PAS = 'NE_SAIT_PAS';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            self::OUI->value => 'Oui',
            self::NON->value => 'Non',
            self::EN_COURS->value => 'Ils sont en cours',
            self::NE_SAIT_PAS->value => 'Je ne sais pas',
        ];
    }

    public function labelForUsager(): string
    {
        $labels = [
            self::OUI->value => 'Les travaux ont été faits',
            self::NON->value => 'Il n\'y a pas eu de travaux',
            self::EN_COURS->value => 'Les travaux sont en cours',
            self::NE_SAIT_PAS->value => 'Je ne sais pas',
        ];

        return $labels[$this->name];
    }
}
