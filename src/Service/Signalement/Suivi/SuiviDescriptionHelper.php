<?php

namespace App\Service\Signalement\Suivi;

use App\Entity\Enum\SuiviCategory;
use Twig\Environment;

class SuiviDescriptionHelper
{
    /**
     * Textes fixes (sans variable), calculés à chaque affichage : la description en base est vide et ignorée.
     * Ne jamais y mettre un template avec variables : elles seraient vides à l'affichage.
     */
    private const array DISPLAY_TEMPLATES = [
        SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI->value => [
            SuiviRecipient::DEFAULT->value => 'suivi/injonction_bailleur_reponse_oui.html.twig',
        ],
        SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE->value => [
            SuiviRecipient::DEFAULT->value => 'suivi/injonction_bailleur_reponse_oui_avec_aide.html.twig',
        ],
        SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI_DEMARCHES_COMMENCEES->value => [
            SuiviRecipient::DEFAULT->value => 'suivi/injonction_bailleur_reponse_oui_demarches_commencees.html.twig',
        ],
        SuiviCategory::INJONCTION_BAILLEUR_REPONSE_NON->value => [
            SuiviRecipient::DEFAULT->value => 'suivi/injonction_bailleur_reponse_non.html.twig',
        ],
        SuiviCategory::INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR->value => [
            SuiviRecipient::DEFAULT->value => 'suivi/injonction_bailleur_bascule_procedure_par_bailleur.html.twig',
        ],
        SuiviCategory::INJONCTION_BAILLEUR_DEMANDE_CLOTURE_PAR_BAILLEUR->value => [
            SuiviRecipient::USAGER->value => 'suivi/injonction_bailleur_demande_cloture_par_bailleur_usager.html.twig',
            SuiviRecipient::DEFAULT->value => 'suivi/injonction_bailleur_demande_cloture_par_bailleur.html.twig',
        ],
        SuiviCategory::INJONCTION_BAILLEUR_RAPPEL_REPONSE_BAILLEUR->value => [
            SuiviRecipient::DEFAULT->value => 'suivi/injonction_bailleur_rappel_reponse_bailleur.html.twig',
        ],
        SuiviCategory::INJONCTION_BAILLEUR_REMINDER_FOR_BAILLEUR->value => [
            SuiviRecipient::DEFAULT->value => 'suivi/injonction_bailleur_reminder_for_bailleur.html.twig',
        ],
        SuiviCategory::INJONCTION_BAILLEUR_REMINDER_FOR_USAGER->value => [
            SuiviRecipient::DEFAULT->value => 'suivi/injonction_bailleur_reminder_for_usager.html.twig',
        ],
        SuiviCategory::INJONCTION_BAILLEUR_CLOTURE_SANS_ACTIVITE->value => [
            SuiviRecipient::DEFAULT->value => 'suivi/injonction_bailleur_cloture_sans_activite.html.twig',
        ],
        SuiviCategory::INJONCTION_BAILLEUR_EXPIREE->value => [
            SuiviRecipient::DEFAULT->value => 'suivi/injonction_bailleur_expiree.html.twig',
        ],
        SuiviCategory::SIGNALEMENT_IS_INJONCTION->value => [
            SuiviRecipient::DEFAULT->value => 'suivi/signalement_is_injonction.html.twig',
        ],
    ];

    /**
     * Textes avec variables, générés une seule fois à la création du suivi puis enregistrés en base.
     * Jamais lus à l'affichage : c'est la description en base qui fait foi.
     * Une même catégorie peut avoir plusieurs textes selon le contexte de création (SuiviDescriptionVariant).
     */
    private const array CREATION_TEMPLATES = [
        SuiviCategory::INJONCTION_BAILLEUR_RELANCE_USAGER_CLOTURE->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/injonction_bailleur_relance_usager_cloture.html.twig',
        ],
        SuiviCategory::INJONCTION_BAILLEUR_CLOTURE_PAR_ADMIN->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/injonction_bailleur_cloture_par_admin.html.twig',
        ],
        SuiviCategory::DEMANDE_ABANDON_PROCEDURE->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/demande_abandon_procedure.html.twig',
        ],
        SuiviCategory::DEMANDE_POURSUITE_PROCEDURE->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/demande_poursuite_procedure.html.twig',
        ],
        SuiviCategory::INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_USAGER->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/injonction_bailleur_bascule_procedure_par_usager.html.twig',
            SuiviDescriptionVariant::REPONSE_DEMANDE_CLOTURE->value => 'suivi/injonction_bailleur_bascule_procedure_par_usager_reponse_demande_cloture.html.twig',
        ],
        SuiviCategory::INJONCTION_BAILLEUR_CLOTURE_PAR_USAGER->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/injonction_bailleur_cloture_par_usager.html.twig',
            SuiviDescriptionVariant::REPONSE_DEMANDE_CLOTURE->value => 'suivi/injonction_bailleur_cloture_par_usager_reponse_demande_cloture.html.twig',
        ],
        SuiviCategory::SIGNALEMENT_EDITED_BO->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/signalement_edited.html.twig',
            SuiviDescriptionVariant::INVITATION_TIERS->value => 'suivi/signalement_edited_bo_invitation_tiers.html.twig',
        ],
        SuiviCategory::SIGNALEMENT_EDITED_FO->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/signalement_edited.html.twig', // Même template que SIGNALEMENT_EDITED_BO
            SuiviDescriptionVariant::INVITATION_TIERS_ACCEPTEE->value => 'suivi/signalement_edited_fo_invitation_tiers_acceptee.html.twig',
            SuiviDescriptionVariant::INVITATION_TIERS_REFUSEE->value => 'suivi/signalement_edited_fo_invitation_tiers_refusee.html.twig',
            SuiviDescriptionVariant::MODIFICATION_EMAIL_OCCUPANT->value => 'suivi/signalement_edited_fo_modification_email_occupant.html.twig',
        ],
        SuiviCategory::MESSAGE_USAGER_TRAVAUX_MISE_EN_CONFORMITE->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/message_usager_travaux_mise_en_conformite.html.twig',
        ],
        // Pas de variante DEFAULT : les autres MESSAGE_USAGER_POST_CLOTURE sont des messages libres de l'usager
        SuiviCategory::MESSAGE_USAGER_POST_CLOTURE->value => [
            SuiviDescriptionVariant::TRAVAUX_MISE_EN_CONFORMITE->value => 'suivi/message_usager_post_cloture_travaux_mise_en_conformite.html.twig',
        ],
        SuiviCategory::NEW_DOCUMENT->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/new_document.html.twig',
        ],
        SuiviCategory::ASK_DOCUMENT->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/ask_document.html.twig',
        ],
        SuiviCategory::SIGNALEMENT_STATUS_IS_SYNCHRO->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/signalement_status_is_synchro.html.twig',
            SuiviDescriptionVariant::IDOSS->value => 'suivi/signalement_status_is_synchro_idoss.html.twig',
        ],
        SuiviCategory::MESSAGE_ESABORA_SCHS->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/message_esabora_schs.html.twig',
        ],
        SuiviCategory::SIGNALEMENT_IS_CLOSED->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/signalement_is_closed.html.twig',
            SuiviDescriptionVariant::CLOTURE_AUTOMATIQUE_INJONCTION->value => 'suivi/signalement_is_closed_cloture_automatique_injonction.html.twig',
        ],
        // Même template que SIGNALEMENT_IS_CLOSED
        SuiviCategory::AFFECTATION_IS_CLOSED->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/signalement_is_closed.html.twig',
        ],
        SuiviCategory::SIGNALEMENT_IS_REFUSED->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/signalement_is_refused.html.twig',
        ],
        SuiviCategory::AFFECTATION_IS_REFUSED->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/affectation_is_refused.html.twig',
        ],
        SuiviCategory::SIGNALEMENT_IS_REOPENED->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/signalement_is_reopened.html.twig',
        ],
        SuiviCategory::INTERVENTION_PLANNED_REMINDER->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/intervention_planned_reminder.html.twig',
        ],
        SuiviCategory::INTERVENTION_IS_ABORTED->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/intervention_is_aborted.html.twig',
        ],
        SuiviCategory::INTERVENTION_IS_CANCELED->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/intervention_is_canceled.html.twig',
        ],
        SuiviCategory::INTERVENTION_HAS_CONCLUSION->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/intervention_has_conclusion.html.twig',
        ],
        SuiviCategory::INTERVENTION_HAS_CONCLUSION_EDITED->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/intervention_has_conclusion_edited.html.twig',
        ],
        SuiviCategory::INTERVENTION_IS_CREATED->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/intervention_is_created.html.twig',
        ],
        SuiviCategory::INTERVENTION_IS_DONE->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/intervention_is_created.html.twig', // Même template que INTERVENTION_IS_CREATED
        ],
        SuiviCategory::INTERVENTION_CONTROLE_IS_CREATED->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/intervention_is_created.html.twig', // Même template que INTERVENTION_IS_CREATED
        ],
        SuiviCategory::INTERVENTION_CONTROLE_IS_DONE->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/intervention_is_created.html.twig', // Même template que INTERVENTION_IS_CREATED
        ],
        SuiviCategory::INTERVENTION_IS_RESCHEDULED->value => [
            SuiviDescriptionVariant::DEFAULT->value => 'suivi/intervention_is_rescheduled.html.twig',
            SuiviDescriptionVariant::ESABORA->value => 'suivi/intervention_is_rescheduled_esabora.html.twig',
        ],
        // Pas de variante DEFAULT : la date d'une visite de contrôle n'est modifiée que depuis Esabora
        SuiviCategory::INTERVENTION_CONTROLE_IS_RESCHEDULED->value => [
            SuiviDescriptionVariant::ESABORA->value => 'suivi/intervention_is_rescheduled_esabora.html.twig',
        ],
    ];

    private const SPECIFIC_DESCRIPTIONS = [
        SuiviCategory::ASK_FEEDBACK_SENT->value => [
            SuiviRecipient::DEFAULT->value => 'Un message automatique a été envoyé à l\'usager pour lui demander de mettre à jour sa situation.',
        ],
        SuiviCategory::SIGNALEMENT_IS_ACTIVE->value => [
            SuiviRecipient::DEFAULT->value => 'Signalement validé',
        ],
        SuiviCategory::AFFECTATION_IS_ACCEPTED->value => [
            SuiviRecipient::DEFAULT->value => '<p>Suite à votre signalement, le ou les partenaires compétents sur votre dossier ont été informés et ont validé 
                la prise en charge de votre dossier.<br>Vous serez bientôt contacté(e) pour des informations complémentaires 
                ou pour programmer une visite du logement.</p>
                <p>N\'hésitez pas à partager toute information qui vous semblerait pertinente. 
                Nous reviendrons vers vous également afin de nous assurer de l’avancée des démarches.</p>',
        ],
        SuiviCategory::INTERVENTION_IS_REQUIRED->value => [
            SuiviRecipient::DEFAULT->value => 'La réalisation d\'une visite est nécessaire pour caractériser les désordres signalés.
                Merci de renseigner la date ou les conclusions de la visite afin de poursuivre la prise en charge de ce signalement.',
        ],
        SuiviCategory::INJONCTION_BAILLEUR_LOGIN_BAILLEUR->value => [
            SuiviRecipient::DEFAULT->value => 'Le bailleur s\'est connecté à l\'espace bailleur',
        ],
    ];

    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    /**
     * Description calculée à l'affichage, ou null si la catégorie utilise la description enregistrée en base.
     */
    public function getDisplayDescription(SuiviCategory $category, SuiviRecipient $recipient): ?string
    {
        $template = self::DISPLAY_TEMPLATES[$category->value][$recipient->value] ?? self::DISPLAY_TEMPLATES[$category->value][SuiviRecipient::DEFAULT->value] ?? null;
        if (null !== $template) {
            return trim($this->twig->render($template));
        }

        return self::getSpecificDescriptionForCategoryAndRecipient($category, $recipient);
    }

    /**
     * Génère la description à enregistrer en base lors de la création du suivi,
     * pour les catégories dont le texte contient des variables.
     *
     * @param array<string, mixed> $params
     */
    public function buildDescriptionForCreation(
        SuiviCategory $category,
        array $params = [],
        SuiviDescriptionVariant $variant = SuiviDescriptionVariant::DEFAULT,
    ): string {
        $template = self::CREATION_TEMPLATES[$category->value][$variant->value] ?? null;
        if (null === $template) {
            throw new \LogicException(\sprintf('No creation template for suivi category %s and variant %s', $category->value, $variant->value));
        }

        return trim($this->twig->render($template, $params));
    }

    public static function getSpecificDescriptionForCategoryAndRecipient(SuiviCategory $category, SuiviRecipient $recipient): ?string
    {
        return self::SPECIFIC_DESCRIPTIONS[$category->value][$recipient->value] ?? self::SPECIFIC_DESCRIPTIONS[$category->value][SuiviRecipient::DEFAULT->value] ?? null;
    }
}
