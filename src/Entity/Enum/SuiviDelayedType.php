<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum SuiviDelayedType: string
{
    use EnumTrait;
    case FO_EDIT_ADRESSE_LOGEMENT = 'FO_EDIT_ADRESSE_LOGEMENT';
    case FO_EDIT_COORDONNEES_OCCUPANT = 'FO_EDIT_COORDONNEES_OCCUPANT';
    case FO_EDIT_COORDONNEES_BAILLEUR = 'FO_EDIT_COORDONNEES_BAILLEUR';
    case FO_EDIT_COORDONNEES_AGENCE = 'FO_EDIT_COORDONNEES_AGENCE';
    case FO_EDIT_COORDONNEES_SYNDIC = 'FO_EDIT_COORDONNEES_SYNDIC';
    case FO_EDIT_INFORMATIONS_ASSURANCE = 'FO_EDIT_INFORMATIONS_ASSURANCE';
    case FO_EDIT_SITUATION_FOYER = 'FO_EDIT_SITUATION_FOYER';
    case FO_EDIT_INFORMATIONS_GENERALES = 'FO_EDIT_INFORMATIONS_GENERALES';
    case FO_EDIT_TYPE_COMPOSITION = 'FO_EDIT_TYPE_COMPOSITION';
    case FO_INVITATION_SENT = 'FO_INVITATION_SENT';
    case FO_ADD_DOCUMENTS = 'FO_ADD_DOCUMENTS';
    case FO_FILE_DELETED = 'FO_FILE_DELETED';

    case BO_EDIT_OCCUPATION_LOGEMENT = 'BO_EDIT_OCCUPATION_LOGEMENT';
    case BO_EDIT_ADDRESS = 'BO_EDIT_ADDRESS';
    case BO_EDIT_COORDONNEES_TIERS = 'BO_EDIT_COORDONNEES_TIERS';
    case BO_EDIT_COORDONNEES_FOYER = 'BO_EDIT_COORDONNEES_FOYER';
    case BO_EDIT_COORDONNEES_BAILLEUR = 'BO_EDIT_COORDONNEES_BAILLEUR';
    case BO_EDIT_COORDONNEES_AGENCE = 'BO_EDIT_COORDONNEES_AGENCE';
    case BO_EDIT_COORDONNEES_SYNDIC = 'BO_EDIT_COORDONNEES_SYNDIC';
    case BO_EDIT_INFORMATIONS_LOGEMENT = 'BO_EDIT_INFORMATIONS_LOGEMENT';
    case BO_EDIT_DESCRIPTION_LOGEMENT = 'BO_EDIT_DESCRIPTION_LOGEMENT';
    case BO_EDIT_SITUATION_FOYER = 'BO_EDIT_SITUATION_FOYER';
    case BO_EDIT_PROCEDURE_DEMARCHES = 'BO_EDIT_PROCEDURE_DEMARCHES';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            self::FO_EDIT_ADRESSE_LOGEMENT->name => 'Adresse du logement',
            self::FO_EDIT_COORDONNEES_OCCUPANT->name => 'Coordonnées de l\'occupant',
            self::FO_EDIT_COORDONNEES_BAILLEUR->name => 'Coordonnées du bailleur',
            self::FO_EDIT_COORDONNEES_AGENCE->name => 'Coordonnées de l\'agence',
            self::FO_EDIT_COORDONNEES_SYNDIC->name => 'Coordonnées du syndic',
            self::FO_EDIT_INFORMATIONS_ASSURANCE->name => 'Informations sur l\'assurance',
            self::FO_EDIT_SITUATION_FOYER->name => 'Situation du foyer',
            self::FO_EDIT_INFORMATIONS_GENERALES->name => 'Informations générales',
            self::FO_EDIT_TYPE_COMPOSITION->name => 'Type et composition du logement',
            self::FO_INVITATION_SENT->name => 'Envoi d\'une invitation à un tiers par l\'usager',
            self::FO_ADD_DOCUMENTS->name => 'Ajout de documents',
            self::FO_FILE_DELETED->name => 'Fichier(s) supprimé(s)',

            self::BO_EDIT_OCCUPATION_LOGEMENT->name => 'Édition de l\'occupation du logement',
            self::BO_EDIT_ADDRESS->name => 'Édition de l\'adresse du logement',
            self::BO_EDIT_COORDONNEES_TIERS->name => 'Édition des coordonnées tiers',
            self::BO_EDIT_COORDONNEES_FOYER->name => 'Édition des coordonnées du foyer',
            self::BO_EDIT_COORDONNEES_BAILLEUR->name => 'Édition des coordonnées du bailleur',
            self::BO_EDIT_COORDONNEES_AGENCE->name => 'Édition des coordonnées de l\'agence',
            self::BO_EDIT_COORDONNEES_SYNDIC->name => 'Édition des coordonnées du syndic',
            self::BO_EDIT_INFORMATIONS_LOGEMENT->name => 'Édition des informations sur le logement',
            self::BO_EDIT_DESCRIPTION_LOGEMENT->name => 'Édition de la description du logement',
            self::BO_EDIT_SITUATION_FOYER->name => 'Édition de la situation du foyer',
            self::BO_EDIT_PROCEDURE_DEMARCHES->name => 'Édition des procédures et démarches',
        ];
    }
}
