<?php

namespace App\Entity\Enum;

enum HistoryEntryEvent: string
{
    case LOGIN = 'LOGIN';
    case LOGIN_2FA = 'LOGIN_2FA';
}
