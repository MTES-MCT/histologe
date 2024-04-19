<?php

namespace App\Entity\Enum;

enum AffectationStatus: int
{
    case STATUS_WAIT = 0;
    case STATUS_ACCEPTED = 1;
    case STATUS_REFUSED = 2;
    case STATUS_CLOSED = 3;

    public function mapSignalementStatus(): int
    {
        return match ($this) {
            self::STATUS_WAIT => SignalementStatus::NEED_VALIDATION->value,
            self::STATUS_ACCEPTED => SignalementStatus::ACTIVE->value,
            self::STATUS_CLOSED, self::STATUS_REFUSED => SignalementStatus::CLOSED->value,
        };
    }

    public function label(): string
    {
        return self::getLabel($this);
    }

    public static function getLabel(self $value): string
    {
        return match ($value) {
            self::STATUS_WAIT => 'nouveau',
            self::STATUS_ACCEPTED => 'en cours',
            self::STATUS_CLOSED => 'fermé',
            self::STATUS_REFUSED => 'refusé',
        };
    }

    public static function mapFilterStatus(string $label): int
    {
        return match ($label) {
            'en_attente' => AffectationStatus::STATUS_WAIT->value,
            'accepte' => AffectationStatus::STATUS_ACCEPTED->value,
            'refuse' => AffectationStatus::STATUS_REFUSED->value,
        };
    }
}
