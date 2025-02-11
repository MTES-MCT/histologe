<?php

namespace App\Entity\Enum;

enum AffectationNewStatus: string
{
    case NOUVEAU = 'NOUVEAU';
    case EN_COURS = 'EN_COURS';
    case FERME = 'FERME';
    case REFUSE = 'REFUSE';

    public static function mapStatus(string $status): ?int
    {
        return match ($status) {
            AffectationNewStatus::NOUVEAU->value => 0,
            AffectationNewStatus::EN_COURS->value => 1,
            AffectationNewStatus::REFUSE->value => 2,
            AffectationNewStatus::FERME->value => 3,
            default => throw new \UnexpectedValueException('Unexpected affectation status : '.$status),
        };
    }
}
