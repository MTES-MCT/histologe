<?php

namespace App\Service\Mailer;

enum NotificationMailerType
{
    case TYPE_ACCOUNT_ACTIVATION_FROM_BO;
    case TYPE_ACCOUNT_ACTIVATION_FROM_FO;
    case TYPE_ACCOUNT_ACTIVATION_REMINDER;
    case TYPE_ACCOUNT_DELETE;
    case TYPE_ACCOUNT_TRANSFER;
    case TYPE_ACCOUNT_REACTIVATION;
    case TYPE_LOST_PASSWORD;
    case TYPE_SIGNALEMENT_NEW;
    case TYPE_ASSIGNMENT_NEW;
    case TYPE_SIGNALEMENT_VALIDATION;
    case TYPE_SIGNALEMENT_REFUSAL;
    case TYPE_SIGNALEMENT_CLOSED_TO_USAGER;
    case TYPE_SIGNALEMENT_CLOSED_TO_PARTNERS;
    case TYPE_SIGNALEMENT_CLOSED_TO_PARTNER;
    case TYPE_SIGNALEMENT_FEEDBACK_USAGER;
    case TYPE_CONFIRM_RECEPTION;
    case TYPE_NEW_COMMENT_FRONT;
    case TYPE_NEW_COMMENT_BACK;
    case TYPE_CONTACT_FORM;
    case TYPE_ERROR_SIGNALEMENT;
    case TYPE_ERROR_SIGNALEMENT_NO_USER;
    case TYPE_CRON;
}
