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
            'VISITE_CONTROLE' => 'Visite de contrôle',
            'ARRETE' => 'Arrêté préfectoral',
        ];
    }

    public static function fromLabel(string $label): self
    {
        $interventionTypeLabel = array_keys(
            array_filter(self::getLabelList(), function (string $labelItem) use ($label) {
                return $label === $labelItem;
            }));

        return self::tryFrom(array_shift($interventionTypeLabel));
    }
}
