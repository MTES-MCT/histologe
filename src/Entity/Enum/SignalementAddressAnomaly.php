<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum SignalementAddressAnomaly: string
{
    use EnumTrait;

    case MISSING_CP_AND_INSEE = 'MISSING_CP_AND_INSEE';
    case INVALID_INSEE_FORMAT = 'INVALID_INSEE_FORMAT';
    case INVALID_CP_FORMAT = 'INVALID_CP_FORMAT';
    case INCONSISTENT_CP_INSEE = 'INCONSISTENT_CP_INSEE';
    case INCONSISTENT_TERRITORY = 'INCONSISTENT_TERRITORY';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            self::MISSING_CP_AND_INSEE->name => 'Absence de code postal et de code INSEE',
            self::INVALID_INSEE_FORMAT->name => 'Format du code INSEE invalide',
            self::INVALID_CP_FORMAT->name => 'Format du code postal invalide',
            self::INCONSISTENT_CP_INSEE->name => 'Code postal et code INSEE incohérents entre eux',
            self::INCONSISTENT_TERRITORY->name => 'Territoire incohérent',
        ];
    }
}
