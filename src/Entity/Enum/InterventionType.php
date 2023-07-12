<?php

namespace App\Entity\Enum;

enum InterventionType: string
{
    case VISITE = 'VISITE';
    case VISITE_CONTROLE = 'VISITE_CONTROLE';
    case ARRETE_PREFECTORAL = 'ARRETE_PREFECTORAL';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    public static function getLabelList(): array
    {
        return [
            'VISITE' => 'Visite',
            'VISITE_CONTROLE' => 'Visite contrôle',
            'ARRETE_PREFECTORAL' => 'Arrêté préfectoral',
        ];
    }

    public static function fromLabel(string $label): ?self
    {
        $key = array_search($label, self::getLabelList());

        return self::tryFrom($key);
    }
}
