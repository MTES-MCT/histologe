<?php

namespace App\Entity\Enum;

enum BrevoEvent: string
{
    case BLOCKED = 'blocked';
    case HARD_BOUNCE = 'hard_bounce';
    case SOFT_BOUNCE = 'soft_bounce';
    case SPAM = 'spam';
    case INVALID_EMAIL = 'invalid_email';
}
