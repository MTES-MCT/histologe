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
    case EN_MEDIATION = 'EN_MEDIATION';

    public function mapAffectationStatus(): string
    {
        return match ($this) {
            self::EN_MEDIATION, self::DRAFT, self::NEED_VALIDATION => AffectationStatus::WAIT->value,
            self::ACTIVE => AffectationStatus::ACCEPTED->value,
            self::REFUSED => AffectationStatus::REFUSED->value,
            self::CLOSED, self::ARCHIVED, self::DRAFT_ARCHIVED => AffectationStatus::CLOSED->value,
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
            self::ARCHIVED->name => 'archivé',
            self::DRAFT_ARCHIVED->name => 'brouillon archivé',
            self::EN_MEDIATION->name => 'en médiation',
        ];
    }

    public static function mapFilterStatus(string $label): string
    {
        return match ($label) {
            'brouillon' => SignalementStatus::DRAFT->value,
            'nouveau' => SignalementStatus::NEED_VALIDATION->value,
            'en_cours' => SignalementStatus::ACTIVE->value,
            'ferme' => SignalementStatus::CLOSED->value,
            'refuse' => SignalementStatus::REFUSED->value,
            'en_mediation' => SignalementStatus::EN_MEDIATION->value,
            default => throw new \UnexpectedValueException('Unexpected signalement status : '.$label),
        };
    }

    /** @return array<SignalementStatus> */
    public static function excludedStatuses(): array
    {
        return [
            self::ARCHIVED,
            self::DRAFT,
            self::DRAFT_ARCHIVED,
            self::EN_MEDIATION,
        ];
    }

    /** @return array<string> */
    public static function excludedStatusesValue(): array
    {
        return [
            self::ARCHIVED->value,
            self::DRAFT->value,
            self::DRAFT_ARCHIVED->value,
            self::EN_MEDIATION->value,
        ];
    }
}
