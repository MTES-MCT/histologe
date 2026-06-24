<?php

namespace App\Service\Signalement\Suivi;

use App\Entity\Enum\SuiviCategory;

class SuiviDescriptionHelper
{
    private const SPECIFIC_DESCRIPTIONS = [
        SuiviCategory::INJONCTION_BAILLEUR_DEMANDE_CLOTURE_PAR_BAILLEUR->value => [
            'usager' => 'Votre bailleur souhaite terminer la démarche pour le motif suivant : les travaux ont été réalisés. Veuillez confirmer sur la page d\'accueil de votre dossier.',
            'default' => 'Demande de clôture du dossier : les travaux ont été réalisés.',
        ],
    ];

    public static function getSpecificDescriptionForCategoryAndRecipient(SuiviCategory $category, bool $isForUsager): ?string
    {
        if (isset(self::SPECIFIC_DESCRIPTIONS[$category->value])) {
            $descriptions = self::SPECIFIC_DESCRIPTIONS[$category->value];
            if ($isForUsager) {
                return $descriptions['usager'];
            }

            return $descriptions['default'];
        }

        return null;
    }
}
