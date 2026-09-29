<?php

namespace App\Service\Import\AutoAffectationRule;

class AutoAffectationRuleHeader
{
    public const string STATUS = 'Statut';
    public const string PARTNER_TYPE = 'Type de partenaire';
    public const string PROFILE_DECLARANT = 'Profil déclarant';
    public const string PARC = 'Parc';
    public const string ALLOCATAIRE = 'Allocataire';
    public const string ACCOMPAGNEMENT_TRAVAILLEUR_SOCIAL = 'Accompagnement travailleur social';
    public const string DEMANDE_LOGEMENT_SOCIAL = 'Demande logement social';
    public const string INSEE_TO_INCLUDE = 'Code insee inclus';
    public const string INSEE_TO_EXCLUDE = 'Code insee exclus';
    public const string PARTNER_TO_EXCLUDE = 'Id partenaires exclus';
    public const string PROCEDURES_SUSPECTEES = 'Procédures suspectées';

    public const array REQUIRED_HEADERS = [
        self::STATUS,
        self::PARTNER_TYPE,
        self::PROFILE_DECLARANT,
        self::PARC,
        self::ALLOCATAIRE,
        self::ACCOMPAGNEMENT_TRAVAILLEUR_SOCIAL,
        self::DEMANDE_LOGEMENT_SOCIAL,
        self::INSEE_TO_INCLUDE,
        self::INSEE_TO_EXCLUDE,
        self::PARTNER_TO_EXCLUDE,
        self::PROCEDURES_SUSPECTEES,
    ];
}
