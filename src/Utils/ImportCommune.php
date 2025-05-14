<?php

namespace App\Utils;

class ImportCommune
{
    // File found here: https://www.data.gouv.fr/fr/datasets/codes-postaux/
    public const COMMUNE_LIST_CSV_PATH = '/src/DataFixtures/Files/codespostaux.csv';

    public const INDEX_CSV_CODE_POSTAL = 0;
    public const INDEX_CSV_CODE_COMMUNE = 1;
    public const INDEX_CSV_NOM_COMMUNE = 2;

    public static function sanitizeCommuneWithArrondissement(string $nom): string
    {
        return mb_trim(preg_replace('/(\d+e|\d+er) Arrondissement$/', '', $nom));
    }
}
