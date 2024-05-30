<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum SignalementStatus: int
{
    use EnumTrait;

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

    public static function getLabelList(): array
    {
        return [
            self::NEED_VALIDATION->name => 'nouveau',
            self::ACTIVE->name => 'en cours',
            self::NEED_PARTNER_RESPONSE->name => 'en cours',
            self::CLOSED->name => 'fermé',
            self::REFUSED->name => 'refusé',
        ];
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

    public static function mapFilterStatus(string $label): int
    {
        return match ($label) {
            'nouveau' => SignalementStatus::NEED_VALIDATION->value,
            'en_cours' => SignalementStatus::ACTIVE->value,
            'ferme' => SignalementStatus::CLOSED->value,
            'refuse' => SignalementStatus::REFUSED->value,
        };
    }
}
