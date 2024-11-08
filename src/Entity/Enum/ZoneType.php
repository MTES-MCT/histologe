<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum ZoneType: string
{
    use EnumTrait;

    case OPAH = 'OPAH';
    case OPAH_RU = 'OPAH_RU';
    case RHI = 'RHI';
    case ANRU = 'ANRU';
    case PIG = 'PIG';
    case PERMIS_DE_LOUER = 'PERMIS_DE_LOUER';
    case PERMIS_DE_DIVISER = 'PERMIS_DE_DIVISER';
    case EPCI = 'EPCI';
    case AUTRE = 'AUTRE';

    public static function getLabelList(): array
    {
        return [
            self::OPAH->name => 'OPAH',
            self::OPAH_RU->name => 'OPAH-RU',
            self::RHI->name => 'RHI',
            self::ANRU->name => 'ANRU',
            self::PIG->name => 'PIG',
            self::PERMIS_DE_LOUER->name => 'Permis de louer',
            self::PERMIS_DE_DIVISER->name => 'Permis de diviser',
            self::EPCI->name => 'EPCI',
            self::AUTRE->name => 'Autre',
        ];
    }
}
