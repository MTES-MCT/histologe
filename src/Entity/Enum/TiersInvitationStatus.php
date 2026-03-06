<?php

namespace App\Entity\Enum;

enum TiersInvitationStatus: string
{
    case WAITING = 'WAITING';
    case ACCEPTED = 'ACCEPTED';
    case REFUSED = 'REFUSED';
}
