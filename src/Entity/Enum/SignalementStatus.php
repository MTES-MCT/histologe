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
    case INJONCTION_BAILLEUR = 'INJONCTION_BAILLEUR';

    public function mapAffectationStatus(): string
    {
        return match ($this) {
            self::INJONCTION_BAILLEUR, self::DRAFT, self::NEED_VALIDATION => AffectationStatus::WAIT->value,
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
            self::INJONCTION_BAILLEUR->name => 'injonction bailleur',
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
            'injonction_bailleur' => SignalementStatus::INJONCTION_BAILLEUR->value,
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
            self::INJONCTION_BAILLEUR,
        ];
    }

    /** @return array<string> */
    public static function excludedStatusesValue(): array
    {
        return [
            self::ARCHIVED->value,
            self::DRAFT->value,
            self::DRAFT_ARCHIVED->value,
            self::INJONCTION_BAILLEUR->value,
        ];
    }
}
