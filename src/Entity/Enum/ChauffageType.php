<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum ChauffageType: string
{
    use EnumTrait;

    case ELECTRIQUE = 'ELECTRIQUE';
    case GAZ = 'GAZ';
    case AUCUN = 'AUCUN';
    case NSP = 'NSP';

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
}
