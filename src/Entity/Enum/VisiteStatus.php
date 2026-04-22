<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum VisiteStatus: string
{
    use EnumTrait;
    case NON_PLANIFIEE = 'Non planifiée';
    case PLANIFIEE = 'Planifiée';
    case CONCLUSION_A_RENSEIGNER = 'Conclusion à renseigner';
    case TERMINEE = 'Terminée';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            'Non planifiée' => 'Non planifiée',
            'Planifiée' => 'Planifiée',
            'Conclusion à renseigner' => 'Conclusion à renseigner',
            'Terminée' => 'Terminée',
        ];
    }
}
