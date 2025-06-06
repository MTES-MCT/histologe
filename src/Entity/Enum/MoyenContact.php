<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum MoyenContact: string
{
    use EnumTrait;

    case COURRIER = 'COURRIER';
    case EMAIL = 'EMAIL';
    case TELEPHONE = 'TELEPHONE';
    case SMS = 'SMS';
    case AUTRE = 'AUTRE';
    case NSP = 'NSP';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            'COURRIER' => 'Courrier',
            'EMAIL' => 'E-mail',
            'TELEPHONE' => 'Téléphone',
            'SMS' => 'SMS',
            'AUTRE' => 'Autre',
            'NSP' => 'Ne sait pas',
        ];
    }

    public static function tryFromString(?string $value): ?self
    {
        if (!$value) {
            return null;
        }

        $upperValue = strtoupper($value);

        return self::tryFrom($upperValue);
    }
}
