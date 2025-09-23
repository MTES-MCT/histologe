<?php

namespace App\Entity\Enum;

enum ChauffageType: string
{
    case ELECTRIQUE = 'ELECTRIQUE';
    case GAZ = 'GAZ';
    case AUCUN = 'AUCUN';
    case NSP = 'NSP';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            'ELECTRIQUE' => 'Chauffage électrique',
            'GAZ' => 'Chauffage au gaz, bois, éthanol ou fioul',
            'AUCUN' => 'Aucun radiateur ou moyen de chauffage fixe',
            'NSP' => 'Type de chauffage inconnu',
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
