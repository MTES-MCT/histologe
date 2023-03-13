<?php

namespace App\Entity\Enum;

enum SignalementStatus: int
{
    case NEED_VALIDATION = 1;
    case ACTIVE = 2;
    case NEED_PARTNER_RESPONSE = 3;
    case CLOSED = 6;
    case ARCHIVED = 7;
    case REFUSED = 8;
}
