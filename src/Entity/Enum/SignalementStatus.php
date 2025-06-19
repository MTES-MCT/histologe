<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum SignalementStatus: string
{
    use EnumTrait;

    case DRAFT = 'DRAFT';
    case NEED_VALIDATION = 'NEED_VALIDATION';
    case ACTIVE = 'ACTIVE';
    case CLOSED = 'CLOSED';
    case ARCHIVED = 'ARCHIVED';
    case REFUSED = 'REFUSED';
    case DRAFT_ARCHIVED = 'DRAFT_ARCHIVED';

    public function mapAffectationStatus(): string
    {
        return match ($this) {
            self::DRAFT, self::NEED_VALIDATION => AffectationStatus::WAIT->value,
            self::ACTIVE => AffectationStatus::ACCEPTED->value,
            self::CLOSED, self::REFUSED, self::ARCHIVED, self::DRAFT_ARCHIVED => AffectationStatus::CLOSED->value,
        };
    }

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            self::DRAFT->name => 'brouillon',
            self::NEED_VALIDATION->name => 'nouveau',
            self::ACTIVE->name => 'en cours',
            self::CLOSED->name => 'fermé',
            self::REFUSED->name => 'refusé',
        ];
    }

    public static function getLabel(self $value): string
    {
        return match ($value) {
            self::DRAFT => 'brouillon',
            self::NEED_VALIDATION => 'nouveau',
            self::ACTIVE => 'en cours',
            self::CLOSED => 'fermé',
            self::REFUSED => 'refusé',
            self::ARCHIVED => 'archivé',
            self::DRAFT_ARCHIVED => 'brouillon archivé',
        };
    }

    public static function mapFilterStatus(string $label): string
    {
        return match ($label) {
            'brouillon' => SignalementStatus::DRAFT->value,
            'nouveau' => SignalementStatus::NEED_VALIDATION->value,
            'en_cours' => SignalementStatus::ACTIVE->value,
            'ferme' => SignalementStatus::CLOSED->value,
            'refuse' => SignalementStatus::REFUSED->value,
            default => throw new \UnexpectedValueException('Unexpected signalement status : '.$label),
        };
    }
}
