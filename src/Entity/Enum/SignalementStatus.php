<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum SignalementStatus: string
{
    use EnumTrait;

    case DRAFT = 'DRAFT';
    case NEED_VALIDATION = 'NEED_VALIDATION';
    case ACTIVE = 'ACTIVE';
    case NEED_PARTNER_RESPONSE = 'NEED_PARTNER_RESPONSE';
    case CLOSED = 'CLOSED';
    case ARCHIVED = 'ARCHIVED';
    case REFUSED = 'REFUSED';

    public function mapAffectationStatus(): int
    {
        return match ($this) {
            self::DRAFT, self::NEED_VALIDATION => AffectationStatus::STATUS_WAIT->value,
            self::ACTIVE, self::NEED_PARTNER_RESPONSE => AffectationStatus::STATUS_ACCEPTED->value,
            self::CLOSED, self::REFUSED, self::ARCHIVED => AffectationStatus::STATUS_CLOSED->value,
        };
    }

    public static function getLabelList(): array
    {
        return [
            self::DRAFT->name => 'brouillon',
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
            self::DRAFT => 'brouillon',
            self::NEED_VALIDATION => 'nouveau',
            self::ACTIVE, self::NEED_PARTNER_RESPONSE => 'en cours',
            self::CLOSED => 'fermé',
            self::REFUSED => 'refusé',
            self::ARCHIVED => 'archivé',
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

    public static function mapApiStatus(SignalementStatus $status): SignalementApiStatus
    {
        return match ($status) {
            SignalementStatus::DRAFT => SignalementApiStatus::BROUILLON,
            SignalementStatus::NEED_VALIDATION => SignalementApiStatus::NOUVEAU,
            SignalementStatus::NEED_PARTNER_RESPONSE, SignalementStatus::ACTIVE => SignalementApiStatus::EN_COURS,
            SignalementStatus::CLOSED => SignalementApiStatus::FERME,
            SignalementStatus::ARCHIVED => SignalementApiStatus::ARCHIVE,
            SignalementStatus::REFUSED => SignalementApiStatus::REFUSE,
        };
    }
}
