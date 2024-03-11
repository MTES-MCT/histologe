<?php

namespace App\Service\Signalement;

class ZipcodeProvider
{
    public const CORSE_DU_SUD_CODE_DEPARTMENT_2A = '2A';
    public const HAUTE_CORSE_CODE_DEPARTMENT_2B = '2B';
    public const MARTINIQUE_CODE_DEPARTMENT_972 = '972';
    public const LA_REUNION_CODE_DEPARTMENT_974 = '974';

    public static function getZipCode(string $postalCode): string
    {
        $zipChunk = substr(trim($postalCode), 0, 3);

        return match ($zipChunk) {
            '200', '201' => self::CORSE_DU_SUD_CODE_DEPARTMENT_2A,
            '202', '206' => self::HAUTE_CORSE_CODE_DEPARTMENT_2B,
            '972' => self::MARTINIQUE_CODE_DEPARTMENT_972,
            '974' => self::LA_REUNION_CODE_DEPARTMENT_974,
            default => substr($postalCode, 0, 2),
        };
    }
}
