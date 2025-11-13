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

    /** @return array<string, string> */
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

        return null === $key ? null : self::tryFrom($key);
    }

    private static function getKeyFromLabel(string $label): ?string
    {
        $label = mb_trim($label);
        $key = array_search($label, self::getLabelList(), true);

        return (false === $key || !is_string($key)) ? null : $key;
    }
}
