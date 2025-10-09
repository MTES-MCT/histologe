<?php

namespace App\Service\DashboardTabPanel;

class TabBodyType
{
    // Tab Accueil
    public const string TAB_DATA_TYPE_DERNIER_ACTION_DOSSIERS = 'derniers-dossiers';

    // Tab Nouveaux dossiers
    public const string TAB_DATA_TYPE_DOSSIERS_FORM_PRO = 'dossiers-form-pro';
    public const string TAB_DATA_TYPE_DOSSIERS_FORM_USAGER = 'dossiers-form-usager';
    public const string TAB_DATA_TYPE_DOSSIERS_NON_AFFECTATION = 'dossiers-non-affectation';
    public const string TAB_DATA_TYPE_DOSSIERS_NEW_AFFECTATION = 'dossiers-new-affectation';
    public const string TAB_DATA_TYPE_DOSSIERS_NO_AGENT = 'dossiers-no-agent';

    // Tab A fermer
    public const string TAB_DATA_TYPE_DOSSIERS_FERME_PARTENAIRE_TOUS = 'dossiers-ferme-partenaire-tous';
    public const string TAB_DATA_TYPE_DOSSIERS_DEMANDE_FERMETURE_USAGER = 'dossiers-demandes-fermeture-usager';
    public const string TAB_DATA_TYPE_DOSSIERS_RELANCE_USAGER_SANS_REPONSE = 'dossiers-relance-usager-sans-reponse';

    // Tab Messages usagers
    public const string TAB_DATA_TYPE_DOSSIERS_MESSAGES_NOUVEAUX = 'dossiers-messages-nouveaux';
    public const string TAB_DATA_TYPE_DOSSIERS_MESSAGES_APRES_FERMETURE = 'dossiers-messages-apres-fermeture';
    public const string TAB_DATA_TYPE_DOSSIERS_MESSAGES_USAGERS_SANS_REPONSE = 'dossiers-messages-usagers-sans-reponse';

    // Tab A vérifier
    public const string TAB_DATA_TYPE_SANS_ACTIVITE_PARTENAIRE = 'dossiers-sans-activite-partenaire';
    public const string TAB_DATA_TYPE_ADRESSE_EMAIL_A_VERIFIER = 'dossiers-adresse-email-a-verifier';

    // Tab Activite récente
    public const string TAB_DATA_TYPE_DOSSIERS_ACTIVITE_RECENTE = 'dossiers-activite-recente';
}
