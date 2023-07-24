<?php

namespace App\Entity\Enum;

enum InterventionType: string
{
    case VISITE = 'VISITE';
    case VISITE_CONTROLE = 'VISITE_CONTROLE';
    case ARRETE_PREFECTORAL = 'ARRETE_PREFECTORAL';

    public const INTERVENTION_TYPE_LABEL = [
        'VISITE' => 'Visite',
        'VISITE_CONTROLE' => 'Visite de contrôle',
        'ARRETE_PREFECTORAL' => 'Arrêté préfectoral',
    ];

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    public static function getLabelList(): array
    {
        return self::INTERVENTION_TYPE_LABEL;
    }

    public static function tryFromLabel(string $label): ?self
    {
        $label = str_contains($label, 'contrôle') ? self::INTERVENTION_TYPE_LABEL['VISITE_CONTROLE'] : $label;
        $key = array_search($label, self::getLabelList());

        return self::tryFrom($key);
    }
}
