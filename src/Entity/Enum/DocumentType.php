<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum DocumentType: String
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
    case BAILLEUR_REPONSE_BAILLEUR = 'BAILLEUR_REPONSE_BAILLEUR';
    case AUTRE = 'AUTRE';
    case SITUATION = 'SITUATION';
    case VISITE = 'VISITE';

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
            self::BAILLEUR_REPONSE_BAILLEUR->name => 'Réponse bailleur',
            self::AUTRE->name => 'Autre',
            self::SITUATION->name => 'Photo de désordre',
            self::VISITE->name => 'Photo de visite',
        ];
    }

    public static function getPhotosList(): array
    {
        return [
            self::SITUATION->name => self::SITUATION->label(),
            self::VISITE->name => self::VISITE->label(),
            self::AUTRE->name => self::AUTRE->label(),
        ];
    }

    public static function getDocumentsList(): array
    {
        return [
            self::SITUATION_FOYER_BAIL->name => self::SITUATION_FOYER_BAIL->label(),
            self::SITUATION_FOYER_DPE->name => self::SITUATION_FOYER_DPE->label(),
            self::SITUATION_FOYER_ETAT_DES_LIEUX->name => self::SITUATION_FOYER_ETAT_DES_LIEUX->label(),
            self::SITUATION_DIAGNOSTIC_PLOMB_AMIANTE->name => self::SITUATION_DIAGNOSTIC_PLOMB_AMIANTE->label(),
            self::PROCEDURE_MISE_EN_DEMEURE->name => self::PROCEDURE_MISE_EN_DEMEURE->label(),
            self::PROCEDURE_RAPPORT_DE_VISITE->name => self::PROCEDURE_RAPPORT_DE_VISITE->label(),
            self::PROCEDURE_ARRETE_MUNICIPAL->name => self::PROCEDURE_ARRETE_MUNICIPAL->label(),
            self::PROCEDURE_ARRETE_PREFECTORAL->name => self::PROCEDURE_ARRETE_PREFECTORAL->label(),
            self::PROCEDURE_SAISINE->name => self::PROCEDURE_SAISINE->label(),
            self::BAILLEUR_DEVIS_POUR_TRAVAUX->name => self::BAILLEUR_DEVIS_POUR_TRAVAUX->label(),
            self::BAILLEUR_REPONSE_BAILLEUR->name => self::BAILLEUR_REPONSE_BAILLEUR->label(),
            self::AUTRE->name => self::AUTRE->label(),
        ];
    }
}
