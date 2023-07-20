<?php

namespace App\Entity\Enum;

enum SignalementDraftStatus: string
{
    case EN_COURS = 'EN COURS';
    case EN_CUL_DE_SAC = 'EN_CUL_DE_SAC';
    case EN_SIGNALEMENT = 'EN_SIGNALEMENT';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    public static function getLabelList(): array
    {
        return [
            'EN_COURS' => 'En cours',
            'EN_CUL_DE_SAC' => 'EN cul de sac',
            'EN_SIGNALEMENT' => 'En signalement',
        ];
    }
}
