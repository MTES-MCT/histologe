<?php

namespace App\Entity\Enum;

enum SignalementNewStatus: string
{
    case NOUVEAU = 'NOUVEAU';
    case EN_COURS = 'EN_COURS';
    case FERME = 'FERME';
    case ARCHIVE = 'ARCHIVE';
    case REFUSE = 'REFUSE';
}
