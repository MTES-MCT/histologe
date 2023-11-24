<?php

namespace App\Entity\Enum;

enum DesordreCritereZone: string
{
    case BATIMENT = 'BATIMENT';
    case LOGEMENT = 'LOGEMENT';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    public static function getLabelList(): array
    {
        return [
            'BATIMENT' => 'Bâtiment',
            'LOGEMENT' => 'Logement',
        ];
    }

    public static function fromLabel(string $label): self
    {
        $key = self::getKeyFromLabel($label);

        return self::from($key);
    }

    public static function tryFromLabel(string $label): ?self
    {
        $key = self::getKeyFromLabel($label);

        return self::tryFrom($key);
    }

    private static function getKeyFromLabel(string $label): string|int|false
    {
        $label = trim($label);

        return array_search($label, self::getLabelList());
    }
}
