<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

// Documentation Brevo : Liste des événements d'e-mails transactionnels.
// https://developers.brevo.com/docs/transactional-webhooks#:~:text=Transactional%20Email%20events
enum BrevoEvent: string
{
    use EnumTrait;

    case SENT = 'sent';
    case CLICKED = 'clicked';
    case DEFERRED = 'deferred';
    case DELIVERED = 'delivered';
    case SOFT_BOUNCE = 'soft_bounce';
    case SPAM = 'spam';
    case FIRST_OPENED = 'unique_opened';
    case HARD_BOUNCE = 'hard_bounce';
    case OPENED = 'opened';
    case INVALID_EMAIL = 'invalid_email';
    case BLOCKED = 'blocked';
    case ERROR = 'error';
    case UNSUBSCRIBED = 'unsubscribed';
    case PROXY_OPEN = 'proxy_open';
    case UNIQUE_PROXY_OPEN = 'unique_proxy_open';

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
            self::PROXY_OPEN->value,
            self::UNIQUE_PROXY_OPEN->value,
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
            self::UNSUBSCRIBED->value,
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

    /**
     * @return string[]
     */
    public static function getLabelList(): array
    {
        return [
            self::BLOCKED->name => 'Blocked',
            self::HARD_BOUNCE->name => 'Hard Bounce',
            self::SOFT_BOUNCE->name => 'Soft Bounce',
            self::SPAM->name => 'Spam',
            self::INVALID_EMAIL->name => 'Invalid Email',
            self::ERROR->name => 'Error',
            self::DELIVERED->name => 'Delivered',
            self::OPENED->name => 'Opened',
            self::CLICKED->name => 'Clicked',
            self::FIRST_OPENED->name => 'First opening',
            self::SENT->name => 'Sent',
        ];
    }
}
