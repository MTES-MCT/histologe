<?php

namespace App\Service\Mailer;

use App\Entity\Territory;

class NotificationMailer
{
    public const TYPE_ACCOUNT_ACTIVATION = 1;
    public const TYPE_ACCOUNT_ACTIVATION_REMINDER = 11;
    public const TYPE_ACCOUNT_DELETE = 14;
    public const TYPE_ACCOUNT_TRANSFER = 15;
    public const TYPE_ACCOUNT_REACTIVATION = 16;
    public const TYPE_MIGRATION_PASSWORD = 13;
    public const TYPE_LOST_PASSWORD = 2;
    public const TYPE_SIGNALEMENT_NEW = 3;
    public const TYPE_ASSIGNMENT_NEW = 4;
    public const TYPE_SIGNALEMENT_VALIDATION = 5;
    public const TYPE_SIGNALEMENT_REFUSAL = 99;
    public const TYPE_SIGNALEMENT_CLOSED_TO_USAGER = 98;
    public const TYPE_SIGNALEMENT_CLOSED_TO_PARTNERS = 97;
    public const TYPE_SIGNALEMENT_CLOSED_TO_PARTNER = 96;
    public const TYPE_SIGNALEMENT_FEEDBACK_USAGER = 95;
    public const TYPE_CONFIRM_RECEPTION = 6;
    public const TYPE_NEW_COMMENT_FRONT = 7;
    public const TYPE_NEW_COMMENT_BACK = 10;
    public const TYPE_CONTACT_FORM = 8;
    public const TYPE_ERROR_SIGNALEMENT = 9;
    public const TYPE_ERROR_SIGNALEMENT_NO_USER = 12;
    public const TYPE_CRON = 100;

    public function __construct(
        private readonly NotificationMailerRegistry $notificationMailerRegistry
    ) {
    }

    public function send(NotificationMailerType $type, string|array $to, array $params, Territory|null $territory): bool
    {
        $notification = new Notification($type, $to, $params, $territory);

        return $this->notificationMailerRegistry->send($notification);
    }
}
