<?php

namespace App\Service\Signalement;

class ZipcodeProvider
{
    public const CORSE_DU_SUD_CODE_DEPARTMENT_2A = '2A';
    public const HAUTE_CORSE_CODE_DEPARTMENT_2B = '2B';
    public const GUADELOUPE_CODE_DEPARTMENT_971 = '971';
    public const MARTINIQUE_CODE_DEPARTMENT_972 = '972';
    public const GUYANE_CODE_DEPARTMENT_973 = '973';
    public const LA_REUNION_CODE_DEPARTMENT_974 = '974';
    public const ST_PIERRE_ET_MIQUELON_CODE_DEPARTMENT_975 = '975';
    public const MAYOTTE_CODE_DEPARTMENT_976 = '976';
    public const SAINT_BARTHELEMY_CODE_DEPARTMENT_977 = '977';
    public const SAINT_MARTIN_CODE_DEPARTMENT_978 = '978';
    public const WALLIS_ET_FUTUNA_CODE_DEPARTMENT_986 = '986';
    public const POLYNESIE_FRANCAISE_CODE_DEPARTMENT_987 = '987';
    public const NOUVELLE_CALEDONIE_CODE_DEPARTMENT_988 = '988';

    public function getZipCode(string $postalCode): string
    {
        $zipChunk = substr(trim($postalCode), 0, 3);

        return match ($zipChunk) {
            '200', '201' => self::CORSE_DU_SUD_CODE_DEPARTMENT_2A,
            '202', '206' => self::HAUTE_CORSE_CODE_DEPARTMENT_2B,
            '971' => self::GUADELOUPE_CODE_DEPARTMENT_971,
            '972' => self::MARTINIQUE_CODE_DEPARTMENT_972,
            '973' => self::GUYANE_CODE_DEPARTMENT_973,
            '974' => self::LA_REUNION_CODE_DEPARTMENT_974,
            '975' => self::ST_PIERRE_ET_MIQUELON_CODE_DEPARTMENT_975,
            '976' => self::MAYOTTE_CODE_DEPARTMENT_976,
            '977' => self::SAINT_BARTHELEMY_CODE_DEPARTMENT_977,
            '978' => self::SAINT_MARTIN_CODE_DEPARTMENT_978,
            '986' => self::WALLIS_ET_FUTUNA_CODE_DEPARTMENT_986,
            '987' => self::POLYNESIE_FRANCAISE_CODE_DEPARTMENT_987,
            '988' => self::NOUVELLE_CALEDONIE_CODE_DEPARTMENT_988,
            default => substr($postalCode, 0, 2),
        };
    }
}
