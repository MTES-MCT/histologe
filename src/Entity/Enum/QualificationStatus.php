<?php

namespace App\Entity\Enum;

enum QualificationStatus: string
{
    case ARCHIVED = 'ARCHIVED';
    case DANGER_CHECK = 'DANGER_CHECK';
    case INSALUBRITE_CHECK = 'INSALUBRITE_CHECK';
    case INSALUBRITE_MANQUEMENT_CHECK = 'INSALUBRITE_MANQUEMENT_CHECK';
    case NDE_AVEREE = 'NDE_AVEREE';
    case NDE_OK = 'NDE_OK';
    case NDE_CHECK = 'NDE_CHECK';
    case NON_DECENCE_CHECK = 'NON_DECENCE_CHECK';
    case RSD_CHECK = 'RSD_CHECK';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    public static function getLabelList(): array
    {
        return [
            'ARCHIVED' => 'archived',
            'DANGER_CHECK' => 'Suspicion Danger occupant',
            'INSALUBRITE_CHECK' => 'Suspicion Insalubrité',
            'INSALUBRITE_MANQUEMENT_CHECK' => 'Suspicion Manquement à l\'insalubrité',
            'NDE_AVEREE' => 'Non décence énergétique avérée',
            'NDE_OK' => 'Décence énergétique OK',
            'NDE_CHECK' => 'Non décence énergétique à vérifier',
            'NON_DECENCE_CHECK' => 'Suspicion Non décence',
            'RSD_CHECK' => 'Suspicion Non décence/RSD',
        ];
    }
}
