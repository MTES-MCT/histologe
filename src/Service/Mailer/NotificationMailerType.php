<?php

namespace App\Service\Mailer;

enum NotificationMailerType
{
    case TYPE_ACCOUNT_ACTIVATION_FROM_BO;
    case TYPE_ACCOUNT_ACTIVATION_FROM_FO;
    case TYPE_ACCOUNT_ACTIVATION_REMINDER;
    case TYPE_ACCOUNT_DELETE;
    case TYPE_ACCOUNT_REMOVE_FROM_TERRITORY;
    case TYPE_ACCOUNT_TRANSFER;
    case TYPE_ACCOUNT_REACTIVATION;
    case TYPE_ACCOUNT_NEW_TERRITORY;
    case TYPE_ACCOUNTS_SOON_ARCHIVED;
    case TYPE_ACCOUNT_USER_SOON_ARCHIVED;
    case TYPE_AFFECTATION_NEW;
    case TYPE_PROFIL_EDIT_PASSWORD;
    case TYPE_PROFIL_EDIT_EMAIL;
    case TYPE_LOST_PASSWORD;
    case TYPE_SIGNALEMENT_NEW;
    case TYPE_SIGNALEMENT_VALIDATION_TO_USAGER;
    case TYPE_SIGNALEMENT_REFUSAL_TO_USAGER;
    case TYPE_SIGNALEMENT_CLOSED_TO_USAGER;
    case TYPE_SIGNALEMENT_CLOSED_TO_PARTNERS;
    case TYPE_SIGNALEMENT_CLOSED_TO_PARTNER;
    case TYPE_SIGNALEMENT_FEEDBACK_USAGER_WITH_RESPONSE;
    case TYPE_SIGNALEMENT_FEEDBACK_USAGER_WITHOUT_RESPONSE;
    case TYPE_SIGNALEMENT_FEEDBACK_USAGER_THIRD;
    case TYPE_CONFIRM_RECEPTION_TO_USAGER;
    case TYPE_NEW_COMMENT_FRONT_TO_USAGER;
    case TYPE_NEW_COMMENT_BACK;
    case TYPE_VISITE_NEEDED;
    case TYPE_VISITE_CREATED_TO_USAGER;
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
    case TYPE_ARRETE_CREATED_TO_USAGER;
    case TYPE_CONTACT_FORM;
    case TYPE_ERROR_SIGNALEMENT;
    case TYPE_CRON;
    case TYPE_PDF_EXPORT;
    case TYPE_LIST_EXPORT;
    case TYPE_USER_EXPORT;
    case TYPE_INACTIVE_USER_EXPORT;
    case TYPE_SUIVI_SUMMARIES_EXPORT;
    case TYPE_CONTINUE_FROM_DRAFT_TO_USAGER;
    case TYPE_SIGNALEMENT_LIEN_SUIVI_TO_USAGER;
    case TYPE_NOTIFICATIONS_SUMMARY;
}
