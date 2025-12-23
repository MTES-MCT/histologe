<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum DocumentType: string
{
    use EnumTrait;

    case SITUATION_FOYER_BAIL = 'SITUATION_FOYER_BAIL';
    case SITUATION_FOYER_DPE = 'SITUATION_FOYER_DPE';
    case SITUATION_FOYER_ETAT_DES_LIEUX = 'SITUATION_FOYER_ETAT_DES_LIEUX';
    case SITUATION_DIAGNOSTIC_PLOMB_AMIANTE = 'SITUATION_DIAGNOSTIC_PLOMB_AMIANTE';
    case PROCEDURE_MISE_EN_DEMEURE = 'PROCEDURE_MISE_EN_DEMEURE';
    case PROCEDURE_RAPPORT_DE_VISITE = 'PROCEDURE_RAPPORT_DE_VISITE';
    case PROCEDURE_ARRETE_MUNICIPAL = 'PROCEDURE_ARRETE_MUNICIPAL';
    case PROCEDURE_ARRETE_PREFECTORAL = 'PROCEDURE_ARRETE_PREFECTORAL';
    case PROCEDURE_SAISINE = 'PROCEDURE_SAISINE';
    case BAILLEUR_DEVIS_POUR_TRAVAUX = 'BAILLEUR_DEVIS_POUR_TRAVAUX';
    case FACTURE = 'FACTURE';
    case BAILLEUR_REPONSE_BAILLEUR = 'BAILLEUR_REPONSE_BAILLEUR';
    case AUTRE_PROCEDURE = 'AUTRE_PROCEDURE';
    case PHOTO_SITUATION = 'PHOTO_SITUATION';
    case PHOTO_VISITE = 'PHOTO_VISITE';
    case EXPORT = 'EXPORT';
    case GRILLE_DE_VISITE = 'GRILLE_DE_VISITE';
    case PROCEDURE = 'PROCEDURE';
    case MODELE_DE_COURRIER = 'MODELE_DE_COURRIER';
    case GUIDES_ET_AIDE = 'GUIDES_ET_AIDE';
    case COMM_INFORMATION_AUX_USAGERS = 'COMM_INFORMATION_AUX_USAGERS';
    case ANNUAIRE = 'ANNUAIRE';
    case AUTRE = 'AUTRE';
    case ENGAGEMENT_TRAVAUX_BAILLEUR = 'ENGAGEMENT_TRAVAUX_BAILLEUR';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            self::SITUATION_FOYER_BAIL->name => 'Bail',
            self::SITUATION_FOYER_DPE->name => 'DPE',
            self::SITUATION_FOYER_ETAT_DES_LIEUX->name => 'Etat des lieux',
            self::SITUATION_DIAGNOSTIC_PLOMB_AMIANTE->name => 'Diagnostic plomb amiante',
            self::PROCEDURE_MISE_EN_DEMEURE->name => 'Mise en demeure',
            self::PROCEDURE_RAPPORT_DE_VISITE->name => 'Rapport de visite',
            self::PROCEDURE_ARRETE_MUNICIPAL->name => 'Arrêté municipal',
            self::PROCEDURE_ARRETE_PREFECTORAL->name => 'Arrêté préfectoral',
            self::PROCEDURE_SAISINE->name => 'Saisine',
            self::BAILLEUR_DEVIS_POUR_TRAVAUX->name => 'Devis pour travaux',
            self::FACTURE->name => 'Facture',
            self::BAILLEUR_REPONSE_BAILLEUR->name => 'Réponse bailleur',
            self::AUTRE_PROCEDURE->name => 'Autre procédure',
            self::PHOTO_SITUATION->name => 'Photo de désordre',
            self::PHOTO_VISITE->name => 'Photo de visite',
            self::EXPORT->name => 'Export',
            self::GRILLE_DE_VISITE->name => 'Grille de visite',
            self::PROCEDURE->name => 'Procédure',
            self::MODELE_DE_COURRIER->name => 'Modèle de courrier',
            self::GUIDES_ET_AIDE->name => 'Guides et aide',
            self::COMM_INFORMATION_AUX_USAGERS->name => 'Comm / information aux usagers',
            self::ANNUAIRE->name => 'Annuaire',
            self::AUTRE->name => 'Autre',
            self::ENGAGEMENT_TRAVAUX_BAILLEUR->name => 'Engagement travaux bailleur',
        ];
    }

    /** @return array<string> */
    public static function getOrderedPhotosList(): array
    {
        return [
            self::PHOTO_SITUATION->name => self::PHOTO_SITUATION->label(),
            self::AUTRE->name => self::AUTRE->label(),
        ];
    }

    /** @return array<string> */
    public static function getOrderedSituationList(): array
    {
        return [
            self::SITUATION_FOYER_BAIL->name => self::SITUATION_FOYER_BAIL->label(),
            self::SITUATION_FOYER_DPE->name => self::SITUATION_FOYER_DPE->label(),
            self::SITUATION_FOYER_ETAT_DES_LIEUX->name => self::SITUATION_FOYER_ETAT_DES_LIEUX->label(),
            self::SITUATION_DIAGNOSTIC_PLOMB_AMIANTE->name => self::SITUATION_DIAGNOSTIC_PLOMB_AMIANTE->label(),
            self::PHOTO_SITUATION->name => self::PHOTO_SITUATION->label(),
            self::AUTRE->name => self::AUTRE->label(),
        ];
    }

    /** @return array<string> */
    public static function getOrderedProcedureList(): array
    {
        return [
            self::PROCEDURE_MISE_EN_DEMEURE->name => self::PROCEDURE_MISE_EN_DEMEURE->label(),
            self::PROCEDURE_RAPPORT_DE_VISITE->name => self::PROCEDURE_RAPPORT_DE_VISITE->label(),
            self::PROCEDURE_ARRETE_MUNICIPAL->name => self::PROCEDURE_ARRETE_MUNICIPAL->label(),
            self::PROCEDURE_ARRETE_PREFECTORAL->name => self::PROCEDURE_ARRETE_PREFECTORAL->label(),
            self::PROCEDURE_SAISINE->name => self::PROCEDURE_SAISINE->label(),
            self::BAILLEUR_DEVIS_POUR_TRAVAUX->name => self::BAILLEUR_DEVIS_POUR_TRAVAUX->label(),
            self::FACTURE->name => self::FACTURE->label(),
            self::BAILLEUR_REPONSE_BAILLEUR->name => self::BAILLEUR_REPONSE_BAILLEUR->label(),
            self::PHOTO_VISITE->name => self::PHOTO_VISITE->label(),
            self::AUTRE_PROCEDURE->name => self::AUTRE_PROCEDURE->label(),
        ];
    }

    /** @return array<string> */
    public static function getTerritoryFilesList(): array
    {
        return [
            self::GRILLE_DE_VISITE->name => self::GRILLE_DE_VISITE->label(),
            self::PROCEDURE->name => self::PROCEDURE->label(),
            self::MODELE_DE_COURRIER->name => self::MODELE_DE_COURRIER->label(),
            self::GUIDES_ET_AIDE->name => self::GUIDES_ET_AIDE->label(),
            self::COMM_INFORMATION_AUX_USAGERS->name => self::COMM_INFORMATION_AUX_USAGERS->label(),
            self::ANNUAIRE->name => self::ANNUAIRE->label(),
            self::AUTRE->name => self::AUTRE->label(),
        ];
    }
}
