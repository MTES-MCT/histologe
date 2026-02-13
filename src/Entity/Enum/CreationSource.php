<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum CreationSource: string
{
    use EnumTrait;

    case API = 'API';
    case FORM_PRO = 'FORM_PRO';
    case FORM_SERVICE_SECOURS = 'FORM_SERVICE_SECOURS';
    case FORM_USAGER = 'FORM_USAGER';
    case IMPORT = 'IMPORT';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            self::API->name => 'API',
            self::FORM_PRO->name => 'Formulaire pro',
            self::FORM_SERVICE_SECOURS->name => 'Formulaire service secours',
            self::FORM_USAGER->name => 'Formulaire usager',
            self::IMPORT->name => 'Import',
        ];
    }
}
