<?php

namespace App\Entity\Enum;

enum MotifCloture: string
{
    case ABANDON_DE_PROCEDURE_ABSENCE_DE_REPONSE = 'ABANDON_DE_PROCEDURE_ABSENCE_DE_REPONSE';
    case DEPART_OCCUPANT = 'DEPART_OCCUPANT';
    case INSALUBRITE = 'INSALUBRITE';
    case LOGEMENT_DECENT = 'LOGEMENT_DECENT';
    case LOGEMENT_VENDU = 'LOGEMENT_VENDU';
    case NON_DECENCE = 'NON_DECENCE';
    case PERIL = 'PERIL';
    case REFUS_DE_VISITE = 'REFUS_DE_VISITE';
    case REFUS_DE_TRAVAUX = 'REFUS_DE_TRAVAUX';
    case RELOGEMENT_OCCUPANT = 'RELOGEMENT_OCCUPANT';
    case RESPONSABILITE_DE_L_OCCUPANT = 'RESPONSABILITE_DE_L_OCCUPANT';
    case RSD = 'RSD';
    case TRAVAUX_FAITS_OU_EN_COURS = 'TRAVAUX_FAITS_OU_EN_COURS';
    case AUTRE = 'AUTRE';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    public static function getLabelList(): array
    {
        return [
            'ABANDON_DE_PROCEDURE_ABSENCE_DE_REPONSE' => 'Abandon de procédure / absence de réponse',
            'DEPART_OCCUPANT' => 'Départ occupant', // renommage de locataire parti
            'INSALUBRITE' => 'Insalubrité',
            'LOGEMENT_DECENT' => "Logement décent / Pas d'infraction",
            'LOGEMENT_VENDU' => 'Logement vendu',
            'NON_DECENCE' => 'Non décence',
            'PERIL' => 'Mise en sécurité / Péril',
            'REFUS_DE_VISITE' => 'Refus de visite',
            'REFUS_DE_TRAVAUX' => 'Refus de travaux',
            'RELOGEMENT_OCCUPANT' => 'Relogement occupant', // précise Problème résolu
            'RESPONSABILITE_DE_L_OCCUPANT' => "Responsabilité de l'occupant / assurantiel",
            'RSD' => 'RSD',
            'TRAVAUX_FAITS_OU_EN_COURS' => 'Travaux faits ou en cours', // précise Problème résolu
            'AUTRE' => 'Autre',
        ];
    }
}
