<?php

namespace App\Entity\Enum;

enum QualificationStatus: string
{
    case ARCHIVED = 'ARCHIVED';
    case NDE_AVEREE = 'NDE_AVEREE';
    case NDE_OK = 'NDE_OK';
    case NDE_CHECK = 'NDE_CHECK';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    public static function getLabelList(): array
    {
        return [
            'ARCHIVED' => 'archived',
            'NDE_AVEREE' => 'Non décence énergétique avérée',
            'NDE_OK' => 'Décence énergétique OK',
            'NDE_CHECK' => 'Non décence énergétique à vérifier',
        ];
    }
}
