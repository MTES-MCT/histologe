<?php

namespace App\Entity\Enum;

enum QualificationStatus: string
{
    case ARCHIVED = 'ARCHIVED';
    case DANGER_CHECK = 'DANGER_CHECK';
    case INSALUBRITE_CHECK = 'INSALUBRITE_CHECK';
    case INSALUBRITE_AVEREE = 'INSALUBRITE_AVEREE';
    case INSALUBRITE_MANQUEMENT_CHECK = 'INSALUBRITE_MANQUEMENT_CHECK';
    case MISE_EN_SECURITE_PERIL_AVEREE = 'MISE_EN_SECURITE_PERIL_AVEREE';
    case NDE_AVEREE = 'NDE_AVEREE';
    case NDE_OK = 'NDE_OK';
    case NDE_CHECK = 'NDE_CHECK';
    case NON_DECENCE_CHECK = 'NON_DECENCE_CHECK';
    case NON_DECENCE_AVEREE = 'NON_DECENCE_AVEREE';
    case RSD_CHECK = 'RSD_CHECK';
    case RSD_AVEREE = 'RSD_AVEREE';

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
            'INSALUBRITE_AVEREE' => 'Insalubrité',
            'INSALUBRITE_MANQUEMENT_CHECK' => 'Suspicion Manquement à la salubrité',
            'MISE_EN_SECURITE_PERIL_AVEREE' => 'Mise en sécurité/Péril',
            'NDE_AVEREE' => 'Non décence énergétique avérée',
            'NDE_OK' => 'Décence énergétique OK',
            'NDE_CHECK' => 'Non décence énergétique à vérifier',
            'NON_DECENCE_CHECK' => 'Suspicion Non décence',
            'NON_DECENCE_AVEREE' => 'Non décence',
            'RSD_CHECK' => 'Suspicion Non décence/RSD',
            'RSD_AVEREE' => 'RSD',
        ];
    }
}
