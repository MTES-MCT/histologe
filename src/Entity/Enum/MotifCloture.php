<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum MotifCloture: string
{
    use EnumTrait;
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
    case DOUBLON = 'DOUBLON';
    case AUTRE = 'AUTRE';
    // Ajout V2
    case DEMANDE_FERMETURE_ABANDON_OCCUPANT = 'DEMANDE_FERMETURE_ABANDON_OCCUPANT';
    case LOGEMENT_MIS_EN_CONFORMITE = 'LOGEMENT_MIS_EN_CONFORMITE';
    case ABSENCE_DE_REPONSE_OCCUPANT = 'ABSENCE_DE_REPONSE_OCCUPANT';
    case GESTION_DU_DOSSIER_EXTERNE = 'GESTION_DU_DOSSIER_EXTERNE';
    case HORS_PERIMETRE_LHI = 'HORS_PERIMETRE_LHI';
    case HORS_COMPETENCE = 'HORS_COMPETENCE';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            self::ABANDON_DE_PROCEDURE_ABSENCE_DE_REPONSE->value => 'Abandon de procédure / absence de réponse',
            self::DEPART_OCCUPANT->value => 'Départ occupant',
            self::INSALUBRITE->value => 'Insalubrité',
            self::LOGEMENT_DECENT->value => 'Pas d\'infraction dans le logement', // précédement 'Logement décent / Pas d'infraction',
            self::LOGEMENT_VENDU->value => 'Logement vendu',
            self::NON_DECENCE->value => 'Non décence',
            self::PERIL->value => 'Mise en sécurité / Péril',
            self::REFUS_DE_VISITE->value => 'Refus de visite',
            self::REFUS_DE_TRAVAUX->value => 'Refus de travaux',
            self::RELOGEMENT_OCCUPANT->value => 'Relogement occupant',
            self::RESPONSABILITE_DE_L_OCCUPANT->value => "Responsabilité de l'occupant / assurantiel",
            self::RSD->value => 'RSD',
            self::TRAVAUX_FAITS_OU_EN_COURS->value => 'Travaux faits ou en cours',
            self::DOUBLON->value => 'Doublon',
            self::AUTRE->value => 'Autre',

            self::LOGEMENT_MIS_EN_CONFORMITE->value => 'Logement mis en conformité',
            self::DEMANDE_FERMETURE_ABANDON_OCCUPANT->value => 'Demande fermeture / abandon occupant',
            self::ABSENCE_DE_REPONSE_OCCUPANT->value => 'Absence de réponse occupant',
            self::GESTION_DU_DOSSIER_EXTERNE->value => 'Gestion du dossier externe',
            self::HORS_PERIMETRE_LHI->value => 'Hors périmètre LHI',
            self::HORS_COMPETENCE->value => 'Hors compétence',
        ];
    }

    /** @return array<MotifCloture> */
    public static function getListForV1(): array
    {
        return [
            self::ABANDON_DE_PROCEDURE_ABSENCE_DE_REPONSE,
            self::DEPART_OCCUPANT,
            self::INSALUBRITE,
            self::LOGEMENT_DECENT,
            self::LOGEMENT_VENDU,
            self::NON_DECENCE,
            self::PERIL,
            self::REFUS_DE_VISITE,
            self::REFUS_DE_TRAVAUX,
            self::RELOGEMENT_OCCUPANT,
            self::RESPONSABILITE_DE_L_OCCUPANT,
            self::RSD,
            self::TRAVAUX_FAITS_OU_EN_COURS,
            self::DOUBLON,
            self::AUTRE,
        ];
    }

    /** @return array<MotifCloture> */
    public static function getListForV2(): array
    {
        return [
            self::LOGEMENT_MIS_EN_CONFORMITE,
            self::RELOGEMENT_OCCUPANT,
            self::DEMANDE_FERMETURE_ABANDON_OCCUPANT,
            self::ABSENCE_DE_REPONSE_OCCUPANT,
            self::REFUS_DE_VISITE,
            self::REFUS_DE_TRAVAUX,
            self::DEPART_OCCUPANT,
            self::LOGEMENT_VENDU,
            self::DOUBLON,
            self::GESTION_DU_DOSSIER_EXTERNE,
            self::HORS_PERIMETRE_LHI,
            self::HORS_COMPETENCE,
            self::LOGEMENT_DECENT,
            self::RESPONSABILITE_DE_L_OCCUPANT,
            self::AUTRE,
        ];
    }

    /** @return array<MotifCloture> */
    public static function getListForAffectation(): array
    {
        // getListForV2
        // moins GESTION_DU_DOSSIER_EXTERNE et HORS_PERIMETRE_LHI
        // plus HORS_COMPETENCE
        // ordre différent
        return [
            self::LOGEMENT_MIS_EN_CONFORMITE,
            self::RELOGEMENT_OCCUPANT,
            self::DEMANDE_FERMETURE_ABANDON_OCCUPANT,
            self::ABSENCE_DE_REPONSE_OCCUPANT,
            self::REFUS_DE_VISITE,
            self::REFUS_DE_TRAVAUX,
            self::DEPART_OCCUPANT,
            self::LOGEMENT_VENDU,
            self::LOGEMENT_DECENT,
            self::RESPONSABILITE_DE_L_OCCUPANT,
            self::DOUBLON,
            self::HORS_COMPETENCE,
            self::AUTRE,
        ];
    }

    /** @return array<MotifCloture> */
    public static function getListForSignalement(): array
    {
        // getListForV2
        // moins HORS_COMPETENCE
        return [
            self::LOGEMENT_MIS_EN_CONFORMITE,
            self::RELOGEMENT_OCCUPANT,
            self::DEMANDE_FERMETURE_ABANDON_OCCUPANT,
            self::ABSENCE_DE_REPONSE_OCCUPANT,
            self::REFUS_DE_VISITE,
            self::REFUS_DE_TRAVAUX,
            self::DEPART_OCCUPANT,
            self::LOGEMENT_VENDU,
            self::DOUBLON,
            self::GESTION_DU_DOSSIER_EXTERNE,
            self::HORS_COMPETENCE,
            self::LOGEMENT_DECENT,
            self::RESPONSABILITE_DE_L_OCCUPANT,
            self::AUTRE,
        ];
    }

    /** @return array<MotifCloture> */
    public static function getListNeedTravauxPrecisions(): array
    {
        return [
            self::LOGEMENT_MIS_EN_CONFORMITE,
            self::RELOGEMENT_OCCUPANT,
            self::DEMANDE_FERMETURE_ABANDON_OCCUPANT,
            self::ABSENCE_DE_REPONSE_OCCUPANT,
            self::DEPART_OCCUPANT,
            self::LOGEMENT_VENDU,
            self::GESTION_DU_DOSSIER_EXTERNE,
            self::AUTRE,
        ];
    }

    /** @return array<MotifCloture> */
    public static function getListForBailleur(): array
    {
        return [
            self::TRAVAUX_FAITS_OU_EN_COURS,
            self::AUTRE,
        ];
    }
}
