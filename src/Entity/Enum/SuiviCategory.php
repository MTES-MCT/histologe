<?php

namespace App\Entity\Enum;

enum SuiviCategory: string
{
    case ASK_DOCUMENT = 'ASK_DOCUMENT';
    case ASK_FEEDBACK_SENT = 'ASK_FEEDBACK_SENT';
    case SIGNALEMENT_IS_ACTIVE = 'SIGNALEMENT_IS_ACTIVE';
    case SIGNALEMENT_IS_CLOSED = 'SIGNALEMENT_IS_CLOSED';
    case SIGNALEMENT_IS_REFUSED = 'SIGNALEMENT_IS_REFUSED';
    case SIGNALEMENT_IS_REOPENED = 'SIGNALEMENT_IS_REOPENED';
    case SIGNALEMENT_EDITED_BO = 'SIGNALEMENT_EDITED_BO';
    case SIGNALEMENT_STATUS_IS_SYNCHRO = 'SIGNALEMENT_STATUS_IS_SYNCHRO';
    case AFFECTATION_IS_ACCEPTED = 'AFFECTATION_IS_ACCEPTED';
    case AFFECTATION_IS_REFUSED = 'AFFECTATION_IS_REFUSED';
    case AFFECTATION_IS_CLOSED = 'AFFECTATION_IS_CLOSED';
    case INTERVENTION_IS_REQUIRED = 'INTERVENTION_IS_REQUIRED';
    case INTERVENTION_IS_CREATED = 'INTERVENTION_IS_CREATED';
    case INTERVENTION_IS_CANCELED = 'INTERVENTION_IS_CANCELED';
    case INTERVENTION_IS_ABORTED = 'INTERVENTION_IS_ABORTED';
    case INTERVENTION_HAS_CONCLUSION = 'INTERVENTION_HAS_CONCLUSION';
    case INTERVENTION_HAS_CONCLUSION_EDITED = 'INTERVENTION_HAS_CONCLUSION_EDITED';
    case INTERVENTION_IS_RESCHEDULED = 'INTERVENTION_IS_RESCHEDULED';
    case INTERVENTION_PLANNED_REMINDER = 'INTERVENTION_PLANNED_REMINDER';
    case NEW_DOCUMENT = 'NEW_DOCUMENT';
    case DOCUMENT_DELETED_BY_USAGER = 'DOCUMENT_DELETED_BY_USAGER';
    case DOCUMENT_DELETED_BY_PARTNER = 'DOCUMENT_DELETED_BY_PARTNER';
    case MESSAGE_USAGER = 'MESSAGE_USAGER';
    case MESSAGE_USAGER_POST_CLOTURE = 'MESSAGE_USAGER_POST_CLOTURE';
    case SIGNALEMENT_EDITED_FO = 'SIGNALEMENT_EDITED_FO';
    case DEMANDE_ABANDON_PROCEDURE = 'DEMANDE_ABANDON_PROCEDURE';
    case DEMANDE_POURSUITE_PROCEDURE = 'DEMANDE_POURSUITE_PROCEDURE';
    case MESSAGE_ESABORA_SCHS = 'MESSAGE_ESABORA_SCHS';
    case MESSAGE_PARTNER = 'MESSAGE_PARTNER';

    // cas liés à l'injonction bailleur
    case INJONCTION_BAILLEUR_REPONSE_OUI = 'INJONCTION_BAILLEUR_REPONSE_OUI';
    case INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE = 'INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE';
    case INJONCTION_BAILLEUR_REPONSE_NON = 'INJONCTION_BAILLEUR_REPONSE_NON';
    case INJONCTION_BAILLEUR_REPONSE_COMMENTAIRE = 'INJONCTION_BAILLEUR_REPONSE_COMMENTAIRE';
    case INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_USAGER = 'INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_USAGER';
    case INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR = 'INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR';
    case INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR_COMMENTAIRE = 'INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR_COMMENTAIRE';
    case INJONCTION_BAILLEUR_EXPIREE = 'INJONCTION_BAILLEUR_EXPIREE';
    case INJONCTION_BAILLEUR_CLOTURE_PAR_USAGER = 'INJONCTION_BAILLEUR_CLOTURE_PAR_USAGER';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    public function labelReponseBailleur(): string
    {
        $reponseList = [
            self::INJONCTION_BAILLEUR_REPONSE_OUI->name => 'Oui',
            self::INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE->name => 'Oui avec aide',
            self::INJONCTION_BAILLEUR_REPONSE_NON->name => 'Non',
        ];

        return $reponseList[$this->name];
    }

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            'ASK_DOCUMENT' => 'Demande de document',
            'ASK_FEEDBACK_SENT' => 'Demande de feedback envoyée à l\'usager',
            'SIGNALEMENT_IS_ACTIVE' => 'Validation du dossier',
            'SIGNALEMENT_IS_CLOSED' => 'Fermeture du dossier',
            'SIGNALEMENT_IS_REFUSED' => 'Refus du dossier',
            'SIGNALEMENT_IS_REOPENED' => 'Réouverture du dossier',
            'SIGNALEMENT_EDITED_BO' => 'Edition du dossier',
            'SIGNALEMENT_STATUS_IS_SYNCHRO' => 'Statut du signalement synchronisé depuis une interconnexion',
            'AFFECTATION_IS_ACCEPTED' => 'Affectation acceptée',
            'AFFECTATION_IS_REFUSED' => 'Affectation refusée',
            'AFFECTATION_IS_CLOSED' => 'Affectation clôturée',
            'INTERVENTION_IS_REQUIRED' => 'Visite requise',
            'INTERVENTION_IS_CREATED' => 'Visite programmée',
            'INTERVENTION_IS_CANCELED' => 'Annulation de la visite programmée',
            'INTERVENTION_IS_ABORTED' => 'La visite n\'a pas pu avoir lieu',
            'INTERVENTION_HAS_CONCLUSION' => 'Conclusion de visite ajoutée',
            'INTERVENTION_HAS_CONCLUSION_EDITED' => 'Edition de la conclusion de visite',
            'INTERVENTION_IS_RESCHEDULED' => 'Changement de date de visite',
            'INTERVENTION_PLANNED_REMINDER' => 'Rappel de visite envoyé',
            'NEW_DOCUMENT' => 'Ajout de documents',
            'DOCUMENT_DELETED_BY_USAGER' => 'Document supprimé par l\'usager',
            'DOCUMENT_DELETED_BY_PARTNER' => 'Document supprimé par le partenaire',
            'MESSAGE_USAGER' => 'Message de l\'usager',
            'MESSAGE_USAGER_POST_CLOTURE' => 'Message de l\'usager après clôture',
            'SIGNALEMENT_EDITED_FO' => 'Édition du dossier par l\'usager',
            'DEMANDE_ABANDON_PROCEDURE' => 'Demande d\'abandon de procédure par l\'usager',
            'DEMANDE_POURSUITE_PROCEDURE' => 'Demande de poursuite de procédure par l\'usager',
            'MESSAGE_ESABORA_SCHS' => 'Message Esabora SCHS',
            'MESSAGE_PARTNER' => 'Suivi du partenaire',
            'INJONCTION_BAILLEUR_REPONSE_OUI' => 'Réponse du bailleur : Oui',
            'INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE' => 'Réponse du bailleur : Oui avec aide',
            'INJONCTION_BAILLEUR_REPONSE_NON' => 'Réponse du bailleur : Non',
            'INJONCTION_BAILLEUR_REPONSE_COMMENTAIRE' => 'Commentaire du bailleur',
            'INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_USAGER' => 'Bascule en procédure administrative par l\'usager',
            'INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR' => 'Le bailleur a souhaité arrêter l\'injonction et basculer en procédure classique',
            'INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR_COMMENTAIRE' => 'Commentaire du bailleur sur l\'arrêt de l\'injonction',
            'INJONCTION_BAILLEUR_EXPIREE' => 'Démarche accélérée expirée',
            'INJONCTION_BAILLEUR_CLOTURE_PAR_USAGER' => 'Clôture du dossier en démarche accélérée par l\'usager',
        ];
    }

    /** @return array<SuiviCategory> */
    public static function CategoriesSubmittedByBailleur(): array
    {
        return [
            self::INJONCTION_BAILLEUR_REPONSE_OUI,
            self::INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE,
            self::INJONCTION_BAILLEUR_REPONSE_NON,
            self::INJONCTION_BAILLEUR_REPONSE_COMMENTAIRE,
            self::INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR,
            self::INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR_COMMENTAIRE,
        ];
    }

    /** @return array<SuiviCategory> */
    public static function injonctionBailleurCategories(): array
    {
        return [
            self::INJONCTION_BAILLEUR_REPONSE_OUI,
            self::INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE,
            self::INJONCTION_BAILLEUR_REPONSE_NON,
            self::INJONCTION_BAILLEUR_REPONSE_COMMENTAIRE,
            self::INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_USAGER,
            self::INJONCTION_BAILLEUR_CLOTURE_PAR_USAGER,
            self::INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR,
            self::INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR_COMMENTAIRE,
            self::INJONCTION_BAILLEUR_EXPIREE,
        ];
    }

    /** @return array<SuiviCategory> */
    public static function injonctionBailleurReponseCategories(): array
    {
        return [
            self::INJONCTION_BAILLEUR_REPONSE_OUI,
            self::INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE,
            self::INJONCTION_BAILLEUR_REPONSE_NON,
        ];
    }
}
