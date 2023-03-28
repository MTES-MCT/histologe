<?php

namespace App\Entity\Enum;

enum Qualification: string
{
    case ACCOMPAGNEMENT_JURIDIQUE = 'ACCOMPAGNEMENT_JURIDIQUE';
    case ACCOMPAGNEMENT_SOCIAL = 'ACCOMPAGNEMENT_SOCIAL';
    case ACCOMPAGNEMENT_TRAVAUX = 'ACCOMPAGNEMENT_TRAVAUX';
    case ARRETES = 'ARRETES';
    case CONCILIATION = 'CONCILIATION';
    case CONSIGNATION_AL = 'CONSIGNATION_AL';
    case DALO = 'DALO';
    case DIOGENE = 'DIOGENE';
    case FSL = 'FSL';
    case HEBERGEMENT_RELOGEMENT = 'HEBERGEMENT_RELOGEMENT';
    case INSALUBRITE = 'INSALUBRITE';
    case MISE_EN_SECURITE_PERIL = 'MISE_EN_SECURITE_PERIL';
    case NON_DECENCE = 'NON_DECENCE';
    case NON_DECENCE_ENERGETIQUE = 'NON_DECENCE_ENERGETIQUE';
    case NUISIBLES = 'NUISIBLES';
    case RSD = 'RSD';
    case VISITES = 'VISITES';
    case DANGER = 'DANGER';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    public static function getLabelList(): array
    {
        return [
            'ACCOMPAGNEMENT_JURIDIQUE' => 'Accompagnement juridique',
            'ACCOMPAGNEMENT_SOCIAL' => 'Accompagnement social',
            'ACCOMPAGNEMENT_TRAVAUX' => 'Accompagnement travaux',
            'ARRETES' => 'Arrêtés',
            'CONCILIATION' => 'Conciliation',
            'CONSIGNATION_AL' => 'Consignation AL',
            'DALO' => 'DALO',
            'DIOGENE' => 'Diogène',
            'FSL' => 'FSL',
            'HEBERGEMENT_RELOGEMENT' => 'Hébergement / relogement',
            'INSALUBRITE' => 'Insalubrité',
            'MISE_EN_SECURITE_PERIL' => 'Mise en sécurité / Péril',
            'NON_DECENCE' => 'Non décence',
            'NON_DECENCE_ENERGETIQUE' => 'Non décence énergétique',
            'NUISIBLES' => 'Nuisibles',
            'RSD' => 'RSD',
            'VISITES' => 'Visites',
            'DANGER' => 'Danger',
        ];
    }
}
