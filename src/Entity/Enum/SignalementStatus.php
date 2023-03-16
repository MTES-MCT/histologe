<?php

namespace App\Entity\Enum;

enum SignalementStatus: int
{
    case NEED_VALIDATION = 1;
    case ACTIVE = 2;
    case NEED_PARTNER_RESPONSE = 3;
    case CLOSED = 6;
    case ARCHIVED = 7;
    case REFUSED = 8;

    public function mapAffectationStatus(): int
    {
        return match ($this) {
            self::NEED_VALIDATION => AffectationStatus::STATUS_WAIT->value,
            self::ACTIVE => AffectationStatus::STATUS_ACCEPTED->value,
            self::CLOSED, self::REFUSED => AffectationStatus::STATUS_CLOSED->value,
        };
    }
}
