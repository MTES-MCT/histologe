<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum AffectationStatus: string
{
    use EnumTrait;

    case WAIT = 'NOUVEAU';
    case ACCEPTED = 'EN_COURS';
    case REFUSED = 'REFUSE';
    case CLOSED = 'FERME';

    public function mapSignalementStatus(): SignalementStatus
    {
        return match ($this) {
            self::WAIT => SignalementStatus::NEED_VALIDATION,
            self::ACCEPTED => SignalementStatus::ACTIVE,
            self::CLOSED, self::REFUSED => SignalementStatus::CLOSED,
        };
    }

    public function label(): string
    {
        return self::getLabel($this);
    }

    public static function getLabel(self $value): string
    {
        return match ($value) {
            self::WAIT => 'nouveau',
            self::ACCEPTED => 'en cours',
            self::CLOSED => 'fermé',
            self::REFUSED => 'refusé',
        };
    }

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            self::WAIT->name => 'nouveau',
            self::ACCEPTED->name => 'en cours',
            self::CLOSED->name => 'fermé',
            self::REFUSED->name => 'refusé',
        ];
    }

    public static function mapFilterStatus(string $label): string
    {
        return match ($label) {
            'en_attente' => AffectationStatus::WAIT->value,
            'accepte' => AffectationStatus::ACCEPTED->value,
            'refuse' => AffectationStatus::REFUSED->value,
            default => throw new \UnexpectedValueException('Unexpected affectation status : '.$label),
        };
    }
}
