<?php

namespace App\Entity\Enum;

enum InterventionType: string
{
    case VISITE = 'VISITE';
    case ARRETE_PREFECTORAL = 'ARRETE_PREFECTORAL';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    public static function getLabelList(): array
    {
        return [
            'VISITE' => 'Visite',
            'ARRETE' => 'Arrêté préfectoral',
        ];
    }
}
