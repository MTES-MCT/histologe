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
    case DEMANDE_ABANDON_PROCEDURE = 'DEMANDE_ABANDON_PROCEDURE';
    case DEMANDE_POURSUITE_PROCEDURE = 'DEMANDE_POURSUITE_PROCEDURE';
    case MESSAGE_ESABORA_SCHS = 'MESSAGE_ESABORA_SCHS';
    case MESSAGE_PARTNER = 'MESSAGE_PARTNER';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
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
            'DEMANDE_ABANDON_PROCEDURE' => 'Demande d\'abandon de procédure par l\'usager',
            'DEMANDE_POURSUITE_PROCEDURE' => 'Demande de poursuite de procédure par l\'usager',
            'MESSAGE_ESABORA_SCHS' => 'Message Esabora SCHS',
            'MESSAGE_PARTNER' => 'Suivi du partenaire',
        ];
    }
}
