<?php

namespace App\Service\Signalement\Suivi;

use App\Entity\Enum\SuiviCategory;

class SuiviDescriptionHelper
{
    private const SPECIFIC_DESCRIPTIONS = [
        SuiviCategory::INJONCTION_BAILLEUR_DEMANDE_CLOTURE_PAR_BAILLEUR->value => [
            SuiviRecipient::USAGER->value => 'Votre bailleur souhaite terminer la démarche pour le motif suivant : les travaux ont été réalisés. Veuillez confirmer sur la page d\'accueil de votre dossier.',
            SuiviRecipient::DEFAULT->value => 'Demande de clôture du dossier : les travaux ont été réalisés.',
        ],
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
    ];

    public static function getSpecificDescriptionForCategoryAndRecipient(SuiviCategory $category, SuiviRecipient $recipient): ?string
    {
        return self::SPECIFIC_DESCRIPTIONS[$category->value][$recipient->value] ?? self::SPECIFIC_DESCRIPTIONS[$category->value][SuiviRecipient::DEFAULT->value] ?? null;
    }
}
