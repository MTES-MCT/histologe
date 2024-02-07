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
    case TYPE_SIGNALEMENT_FEEDBACK_USAGER_WITH_RESPONSE;
    case TYPE_SIGNALEMENT_FEEDBACK_USAGER_WITHOUT_RESPONSE;
    case TYPE_SIGNALEMENT_FEEDBACK_USAGER_THIRD;
    case TYPE_SIGNALEMENT_ASK_BAIL_DPE;
    case TYPE_CONFIRM_RECEPTION;
    case TYPE_NEW_COMMENT_FRONT;
    case TYPE_NEW_COMMENT_BACK;
    case TYPE_VISITE_NEEDED;
    case TYPE_VISITE_CREATED_TO_USAGER;
    case TYPE_VISITE_CREATED_TO_PARTNER;
    case TYPE_VISITE_CANCELED_TO_USAGER;
    case TYPE_VISITE_RESCHEDULED_TO_USAGER;
    case TYPE_VISITE_CONFIRMED_TO_USAGER;
    case TYPE_VISITE_CONFIRMED_TO_PARTNER;
    case TYPE_VISITE_ABORTED_TO_USAGER;
    case TYPE_VISITE_ABORTED_TO_PARTNER;
    case TYPE_VISITE_EDITED_TO_USAGER;
    case TYPE_VISITE_FUTURE_REMINDER_TO_USAGER;
    case TYPE_VISITE_FUTURE_REMINDER_TO_PARTNER;
    case TYPE_VISITE_PAST_REMINDER_TO_PARTNER;
    case TYPE_CONTACT_FORM;
    case TYPE_ERROR_SIGNALEMENT;
    case TYPE_CRON;
    case TYPE_PDF_EXPORT;
}
