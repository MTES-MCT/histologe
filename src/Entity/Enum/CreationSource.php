<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum CreationSource: string
{
    use EnumTrait;

    case API = 'API';
    case FORM_PRO = 'FORM_PRO';
    case FORM_SERVICE_SECOURS = 'FORM_SERVICE_SECOURS';
    case FORM_USAGER_V1 = 'FORM_USAGER_V1';
    case FORM_USAGER_V2 = 'FORM_USAGER_V2';
    case IMPORT = 'IMPORT';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            self::API->name => 'API',
            self::FORM_PRO->name => 'Formulaire pro',
            self::FORM_SERVICE_SECOURS->name => 'Formulaire service secours',
            self::FORM_USAGER_V1->name => 'Formulaire usager V1',
            self::FORM_USAGER_V2->name => 'Formulaire usager V2',
            self::IMPORT->name => 'Import',
        ];
    }

    public static function getV1Sources(): array
    {
        return [
            self::FORM_USAGER_V1->value,
            self::IMPORT->value,
        ];
    }

    public static function getFormUsagerValues(): array
    {
        return [
            self::FORM_USAGER_V1->value,
            self::FORM_USAGER_V2->value,
            self::IMPORT->value,
        ];
    }

    public static function getFormProValues(): array
    {
        return [
            self::FORM_PRO->value,
            self::API->value,
        ];
    }
}
