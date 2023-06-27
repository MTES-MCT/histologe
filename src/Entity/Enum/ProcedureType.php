<?php

namespace App\Entity\Enum;

enum ProcedureType: string
{
    case NON_DECENCE = 'NON_DECENCE';
    case RSD = 'RSD';
    case INSALUBRITE = 'INSALUBRITE';
    case MISE_EN_SECURITE_PERIL = 'MISE_EN_SECURITE_PERIL';
    case LOGEMENT_DECENT = 'LOGEMENT_DECENT';
    case RESPONSABILITE_OCCUPANT_ASSURANTIEL = 'RESPONSABILITE_OCCUPANT_ASSURANTIEL';
    case AUTRE = 'AUTRE';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    public static function getLabelList(): array
    {
        return [
            'NON_DECENCE' => 'Non décence',
            'RSD' => 'Infraction RSD',
            'INSALUBRITE' => 'Insalubrité',
            'MISE_EN_SECURITE_PERIL' => 'Mise en sécurité / Péril',
            'LOGEMENT_DECENT' => 'Logement décent / Pas d\'infraction',
            'RESPONSABILITE_OCCUPANT_ASSURANTIEL' => 'Responsabilité occupant / Assurantiel',
            'AUTRE' => 'Autre',
        ];
    }
}
