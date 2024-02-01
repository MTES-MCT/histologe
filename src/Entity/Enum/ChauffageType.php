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

        return self::tryFrom($key);
    }

    private static function getKeyFromLabel(string $label): string|int|false
    {
        $label = trim($label);

        return array_search($label, self::getLabelList());
    }
}
