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
        ];
    }
}
