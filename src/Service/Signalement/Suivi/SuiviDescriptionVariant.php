<?php

namespace App\Service\Signalement\Suivi;

enum SuiviDescriptionVariant: string
{
    case DEFAULT = 'DEFAULT';
    // Réponse de l'usager à une demande de clôture faite par le bailleur
    case REPONSE_DEMANDE_CLOTURE = 'REPONSE_DEMANDE_CLOTURE';
}
