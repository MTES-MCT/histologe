<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum DebutDesordres: string
{
    use EnumTrait;

    case LESS_1_MONTH = 'LESS_1_MONTH';
    case MONTHS_1_to_6 = 'MONTHS_1_to_6';
    case MONTHS_6_to_12 = 'MONTHS_6_to_12';
    case YEARS_1_TO_2 = 'YEARS_1_TO_2';
    case MORE_2_YEARS = 'MORE_2_YEARS';
    case NSP = 'NSP';

    public static function getLabelList(): array
    {
        return [
            'LESS_1_MONTH' => 'Moins d\'un mois',
            'MONTHS_1_to_6' => 'Entre 1 mois et 6 mois',
            'MONTHS_6_to_12' => 'Entre 6 mois et 1 an',
            'YEARS_1_TO_2' => 'Entre 1 et 2 ans',
            'MORE_2_YEARS' => 'Plus de 2 ans',
            'NSP' => 'Ne sait pas',
        ];
    }
}
