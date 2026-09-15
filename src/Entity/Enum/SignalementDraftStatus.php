<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum SignalementDraftStatus: string
{
    use EnumTrait;

    case EN_COURS = 'EN_COURS';
    case EN_SIGNALEMENT = 'EN_SIGNALEMENT';
    case ARCHIVE = 'ARCHIVE';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            'EN_COURS' => 'En cours',
            'EN_SIGNALEMENT' => 'En signalement',
            'ARCHIVE' => 'Archivé',
        ];
    }
}
