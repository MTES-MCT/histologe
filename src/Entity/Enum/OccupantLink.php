<?php

namespace App\Entity\Enum;

enum OccupantLink: string
{
    case PROCHE = 'PROCHE';
    case VOISIN = 'VOISIN';
    case SECOURS = 'SECOURS';
    case BAILLEUR = 'BAILLEUR';
    case PRO = 'PRO';
    case AUTRE = 'AUTRE';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    /** @see SignalementType::LINK_CHOICES legacy */
    public static function getLabelList(): array
    {
        return [
            'PROCHE' => 'Proche',
            'VOISIN' => 'Voisin',
            'SECOURS' => 'Services de secours',
            'BAILLEUR' => 'Bailleur',
            'PRO' => 'Pro',
            'AUTRE' => 'Autre',
        ];
    }

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }
}
