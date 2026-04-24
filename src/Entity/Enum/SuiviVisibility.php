<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum SuiviVisibility: string
{
    use EnumTrait;

    case BAILLEUR = 'BAILLEUR';
    case USAGERS = 'USAGERS';
    case PARTENAIRES_AFFECTES = 'PARTENAIRES_AFFECTES';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            'BAILLEUR' => 'Bailleur',
            'USAGERS' => 'Usagers',
            'PARTENAIRES_AFFECTES' => 'Partenaires affectés',
        ];
    }

    /** @return array<SuiviVisibility> */
    public static function fromIsPublic(bool $isPublic): array
    {
        if ($isPublic) {
            return [
                self::USAGERS,
                self::PARTENAIRES_AFFECTES,
            ];
        }

        return [
            self::PARTENAIRES_AFFECTES,
        ];
    }
}
