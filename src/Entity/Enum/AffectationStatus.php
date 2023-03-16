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
}
