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
            self::ACTIVE, self::NEED_PARTNER_RESPONSE => AffectationStatus::STATUS_ACCEPTED->value,
            self::CLOSED, self::REFUSED => AffectationStatus::STATUS_CLOSED->value,
        };
    }

    public function label(): string
    {
        return self::getLabel($this);
    }

    public static function getLabel(self $value): string
    {
        return match ($value) {
            self::NEED_VALIDATION => 'nouveau',
            self::ACTIVE, self::NEED_PARTNER_RESPONSE => 'en cours',
            self::CLOSED => 'fermé',
            self::REFUSED => 'refusé',
        };
    }
}
