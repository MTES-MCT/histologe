<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum UserStatus: string
{
    use EnumTrait;

    case INACTIVE = 'INACTIVE';
    case ACTIVE = 'ACTIVE';
    case ARCHIVE = 'ARCHIVE';

    /**
     * @return array<string, string>
     */
    public static function getLabelList(): array
    {
        return [
            self::INACTIVE->name => 'Inactif',
            self::ACTIVE->name => 'Actif',
            self::ARCHIVE->name => 'Archivé',
        ];
    }

    public static function getLabel(self $value): string
    {
        return match ($value) {
            self::INACTIVE => 'Inactif',
            self::ACTIVE => 'Actif',
            self::ARCHIVE => 'Archivé',
        };
    }
}
