<?php

namespace App\Entity\Enum;

enum InterventionStatus: string
{
    case PLANNED = 'PLANNED';
    case DONE = 'DONE';
    case CANCELED = 'CANCELED';
    case NOT_DONE = 'NOT_DONE';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    public static function getLabelList(): array
    {
        return [
            'PLANNED' => 'Planifiée',
            'DONE' => 'Effectuée',
            'CANCELED' => 'Annulée',
            'NOT_DONE' => 'Non effectuée',
        ];
    }
}
