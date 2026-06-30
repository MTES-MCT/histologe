<?php

namespace App\Service\Signalement\Suivi;

enum SuiviRecipient: string
{
    case USAGER = 'USAGER';
    case DEFAULT = 'DEFAULT';
}
