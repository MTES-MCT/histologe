<?php

namespace App\Entity\Enum;

enum SignalementDraftStatus: string
{
    case EN_COURS = 'EN_COURS';
    case HORS_PDLHI = 'HORS_PDLHI';
    case EN_SIGNALEMENT = 'EN_SIGNALEMENT';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    public static function getLabelList(): array
    {
        return [
            'EN_COURS' => 'En cours',
            'HORS_PDLHI' => 'Hors PDLHI',
            'EN_SIGNALEMENT' => 'En signalement',
        ];
    }
}
