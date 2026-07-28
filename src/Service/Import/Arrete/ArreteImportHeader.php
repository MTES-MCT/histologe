<?php

namespace App\Service\Import\Arrete;

class ArreteImportHeader
{
    public const string DATE_ARRETE = 'date arrêté';
    public const string CLASSIFICATION_ARRETE = 'classification arrêté';
    public const string DATE_ARRETE_MAIN_LEVEE = 'date arrêté main levée';
    public const string NUMERO_VOIE = 'numéro de voie';
    public const string NOM_VOIE = 'nom de la voie';
    public const string CODE_POSTAL = 'code postal';
    public const string COMMUNE = 'commune';
    public const string DENOMINATION_SYNDIC = 'dénomination syndic';
    public const string ID_PARCELLAIRE = 'identifiant parcellaire';

    public const array REQUIRED_HEADERS = [
        self::NUMERO_VOIE,
        self::NOM_VOIE,
        self::CODE_POSTAL,
        self::COMMUNE,
        self::CLASSIFICATION_ARRETE,
        self::DATE_ARRETE,
        self::ID_PARCELLAIRE,
    ];
}
