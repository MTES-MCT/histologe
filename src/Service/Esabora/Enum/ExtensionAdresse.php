<?php

namespace App\Service\Esabora\Enum;

enum ExtensionAdresse
{
    case A;
    case B;
    case C;
    case D;
    case Q;
    case T;
    case BIS;
    case TER;
    case QUATER;
    case QUINQUIES;
    case SEXIES;
    case SEPTIES;
    case OCTIES;
    case NONIES;
    case DECIES;

    public static function values(): array
    {
        return array_map(fn ($case) => $case->name, self::cases());
    }
}
