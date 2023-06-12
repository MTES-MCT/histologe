<?php

namespace App\Entity\Enum;

enum VisiteStatus: string
{
    case NON_PLANIFIEE = 'Non planifiée';
    case PLANIFIEE = 'Planifiée';
    case CONCLUSION_A_RENSEIGNER = 'Conclusion à renseigner';
    case TERMINEE = 'Terminée';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

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
