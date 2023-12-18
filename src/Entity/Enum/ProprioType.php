<?php

namespace App\Entity\Enum;

enum ProprioType: string
{
    case PARTICULIER = 'PARTICULIER';
    case ORGANISME_SOCIETE = 'ORGANISME_SOCIETE';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    public static function getLabelList(): array
    {
        return [
            'PARTICULIER' => 'Particulier',
            'ORGANISME_SOCIETE' => 'Organisme / Société',
        ];
    }
}
