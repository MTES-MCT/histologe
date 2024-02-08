<?php

namespace App\Entity\Enum;

enum DocumentType: String
{
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

    public function label(): string
    {
        return self::getLabel($this);
    }

    public static function getLabel(self $value): string
    {
        return match ($value) {
            self::SITUATION_FOYER_BAIL => 'Bail',
            self::SITUATION_FOYER_DPE => 'DPE',
            self::SITUATION_FOYER_ETAT_DES_LIEUX => 'Etat des lieux',
            self::SITUATION_DIAGNOSTIC_PLOMB_AMIANTE => 'Diagnostic plomb-amiante',
            self::PROCEDURE_MISE_EN_DEMEURE => 'Mise en demeure',
            self::PROCEDURE_RAPPORT_DE_VISITE => 'Rapport de visite',
            self::PROCEDURE_ARRETE_MUNICIPAL => 'Arrêté municipal',
            self::PROCEDURE_ARRETE_PREFECTORAL => 'Arrêté préfectoral',
            self::PROCEDURE_SAISINE => 'Saisine',
            self::BAILLEUR_DEVIS_POUR_TRAVAUX => 'Devis pour travaux',
            self::BAILLEUR_REPONSE_BAILLEUR => 'Réponse du bailleur',
            self::AUTRE => 'Autre',
            self::SITUATION => 'Désordre',
            self::VISITE => 'Visite',
        };
    }
}
