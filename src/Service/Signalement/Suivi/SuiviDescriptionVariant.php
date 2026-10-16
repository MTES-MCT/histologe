<?php

namespace App\Service\Signalement\Suivi;

enum SuiviDescriptionVariant: string
{
    case DEFAULT = 'DEFAULT';
    // Réponse de l'usager à une demande de clôture faite par le bailleur
    case REPONSE_DEMANDE_CLOTURE = 'REPONSE_DEMANDE_CLOTURE';
    // Invitation d'un tiers depuis le back-office
    case INVITATION_TIERS = 'INVITATION_TIERS';
    // Invitation d'un tiers acceptée / refusée
    case INVITATION_TIERS_ACCEPTEE = 'INVITATION_TIERS_ACCEPTEE';
    case INVITATION_TIERS_REFUSEE = 'INVITATION_TIERS_REFUSEE';
    // Confirmation par l'occupant de sa nouvelle adresse e-mail
    case MODIFICATION_EMAIL_OCCUPANT = 'MODIFICATION_EMAIL_OCCUPANT';
    // Réponse de l'usager sur la réalisation des travaux de mise en conformité
    case TRAVAUX_MISE_EN_CONFORMITE = 'TRAVAUX_MISE_EN_CONFORMITE';
}
