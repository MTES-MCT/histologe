<?php

namespace App\Service\Signalement\Suivi;

use App\Entity\Enum\SuiviCategory;
use Twig\Environment;

class SuiviDescriptionHelper
{
    public const string DESCRIPTION_MOTIF_CLOTURE_PARTNER = 'Le signalement a été clôturé pour %s avec le motif suivant :';
    public const string DESCRIPTION_TRAVAUX_MISE_EN_CONFORMITE = 'Travaux de mise en conformité réalisés ? ';
    public const string DESCRIPTION_MOTIF_CLOTURE_INJONCTION_ADMIN = 'Un administrateur a clôturé le dossier en démarche accélérée depuis le back-office pour le motif suivant :<br>%s<br>Détails du motif d\'arrêt de procédure : %s';

    private const array SPECIFIC_TEMPLATES = [
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

    public function getDescription(SuiviCategory $category, SuiviRecipient $recipient): ?string
    {
        $template = self::SPECIFIC_TEMPLATES[$category->value][$recipient->value] ?? self::SPECIFIC_TEMPLATES[$category->value][SuiviRecipient::DEFAULT->value] ?? null;
        if (null !== $template) {
            return trim($this->twig->render($template));
        }

        return self::getSpecificDescriptionForCategoryAndRecipient($category, $recipient);
    }

    public static function getSpecificDescriptionForCategoryAndRecipient(SuiviCategory $category, SuiviRecipient $recipient): ?string
    {
        return self::SPECIFIC_DESCRIPTIONS[$category->value][$recipient->value] ?? self::SPECIFIC_DESCRIPTIONS[$category->value][SuiviRecipient::DEFAULT->value] ?? null;
    }
}
