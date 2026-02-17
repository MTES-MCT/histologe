<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum CreationSource: string
{
    use EnumTrait;

    public const string CREATED_FROM_FORMULAIRE_USAGER = 'formulaire-usager'; // valeur tableau de bord
    public const string CREATED_FROM_FORMULAIRE_PRO = 'formulaire-pro'; // valeur tableau de bord

    case API = 'API';
    case FORM_PRO_BO = 'FORM_PRO_BO';
    case FORM_SERVICE_SECOURS = 'FORM_SERVICE_SECOURS';
    case FORM_USAGER_V1 = 'FORM_USAGER_V1';
    case FORM_USAGER_V2 = 'FORM_USAGER_V2';
    case IMPORT = 'IMPORT';

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
            self::FORM_USAGER_V1,
            self::IMPORT,
        ];
    }

    public static function getFormUsagerValues(): array
    {
        return [
            self::FORM_USAGER_V1,
            self::FORM_USAGER_V2,
            self::IMPORT,
        ];
    }

    public static function getFormUsagerValuesListString(): string
    {
        // Les valeurs sont des enum, on veut récupérer une liste des valeurs
        return implode(',', array_map(fn (self $creationSource) => $creationSource->value, self::getFormUsagerValues()));
    }

    public static function getFormProValues(): array
    {
        return [
            self::FORM_PRO_BO,
            self::API,
        ];
    }

    public static function getFormProValuesListString(): string
    {
        return implode(',', array_map(fn (self $creationSource) => $creationSource->value, self::getFormProValues()));
    }
}
