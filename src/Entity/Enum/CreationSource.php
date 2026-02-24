<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum CreationSource: string
{
    use EnumTrait;

    public const string CREATED_FROM_FORMULAIRE_USAGER = 'formulaire-usager'; // valeur tableau de bord
    public const string CREATED_FROM_FORMULAIRE_PRO = 'formulaire-pro'; // valeur tableau de bord

    case API = 'api';
    case FORM_PRO_BO = 'form-pro-bo';
    case FORM_SERVICE_SECOURS = 'form-service-secours';
    case FORM_USAGER_V1 = 'form-usager-v1';
    case FORM_USAGER_V2 = 'form-usager-v2';
    case IMPORT = 'import';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            self::API->name => 'API',
            self::FORM_PRO_BO->name => 'Formulaire pro BO',
            self::FORM_SERVICE_SECOURS->name => 'Formulaire service secours',
            self::FORM_USAGER_V1->name => 'Formulaire usager V1',
            self::FORM_USAGER_V2->name => 'Formulaire usager V2',
            self::IMPORT->name => 'Import',
        ];
    }

    public static function getV1Sources(): array
    {
        return [
            self::FORM_USAGER_V1->name,
            self::IMPORT->name,
        ];
    }

    public static function getFormUsagerValues(): array
    {
        return [
            self::FORM_USAGER_V1->name,
            self::FORM_USAGER_V2->name,
            self::IMPORT->name,
        ];
    }

    public static function getFormProValues(): array
    {
        return [
            self::FORM_PRO_BO->name,
            self::API->name,
        ];
    }

    public static function tryFromInsensitive(string $value): ?self
    {
        return self::tryFrom(strtolower($value));
    }
}
