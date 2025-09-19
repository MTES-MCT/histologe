<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

// Documentation Brevo : Liste des événements d'e-mails transactionnels.
// https://developers.brevo.com/docs/transactional-webhooks#:~:text=Transactional%20Email%20events
enum BrevoEvent: string
{
    use EnumTrait;

    case BLOCKED = 'blocked';
    case HARD_BOUNCE = 'hard_bounce';
    case SOFT_BOUNCE = 'soft_bounce';
    case SPAM = 'spam';
    case INVALID_EMAIL = 'invalid_email';
    case ERROR = 'error';
    case DELIVERED = 'delivered';
    case OPENED = 'opened';
    case CLICKED = 'clicked';
    case FIRST_OPENED = 'unique_opened';
    case SENT = 'sent';

    /**
     * @return string[]
     */
    public static function getSuccessEvents(): array
    {
        return [
            self::DELIVERED->value,
            self::OPENED->value,
            self::CLICKED->value,
            self::FIRST_OPENED->value,
        ];
    }

    /**
     * @return string[]
     */
    public static function getErrorEvents(): array
    {
        return [
            self::BLOCKED->value,
            self::HARD_BOUNCE->value,
            self::SOFT_BOUNCE->value,
            self::SPAM->value,
            self::INVALID_EMAIL->value,
            self::ERROR->value,
        ];
    }

    public static function isErrorEvent(string $event): bool
    {
        return in_array($event, self::getErrorEvents(), true);
    }

    public static function isSuccessEvent(string $event): bool
    {
        return in_array($event, self::getSuccessEvents(), true);
    }
}
