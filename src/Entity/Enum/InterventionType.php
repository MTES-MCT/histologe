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
            'VISITE_CONTROLE' => 'Visite de contrôle',
            'ARRETE' => 'Arrêté préfectoral',
        ];
    }
}
