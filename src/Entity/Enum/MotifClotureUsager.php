<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum MotifClotureUsager: string
{
    use EnumTrait;

    case ACCORD_PROPRIETAIRE = 'ACCORD_PROPRIETAIRE';
    case RELOGEMENT_OCCUPANT = 'RELOGEMENT_OCCUPANT';
    case TRAVAUX_FAITS_OU_EN_COURS = 'TRAVAUX_FAITS_OU_EN_COURS';
    case AUTRE = 'AUTRE';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            'ACCORD_PROPRIETAIRE' => 'Accord avec le propriétaire',
            'RELOGEMENT_OCCUPANT' => 'Changement de logement',
            'TRAVAUX_FAITS_OU_EN_COURS' => 'Le problème est résolu',
            'AUTRE' => 'Autre',
        ];
    }
}
