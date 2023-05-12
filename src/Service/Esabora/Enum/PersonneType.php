<?php

namespace App\Service\Esabora\Enum;

enum PersonneType: string
{
    case DECLARANT = 'D';
    case OCCUPANT = 'O';
    case PROPRIETAIRE = 'P';
    case REFERENT_SOCIAL = 'S';
    case SCI = 'I';
    case SYNDIC = 'Y';

    public static function toArray(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
