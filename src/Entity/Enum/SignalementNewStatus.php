<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum SignalementNewStatus: string
{
    use EnumTrait;

    case NOUVEAU = 'NOUVEAU';
    case EN_COURS = 'EN_COURS';
    case FERME = 'FERME';
    case ARCHIVE = 'ARCHIVE';
    case REFUSE = 'REFUSE';
}
