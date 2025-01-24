<?php

namespace App\Entity\Enum;

enum AffectationNewStatus: string
{
    case NOUVEAU = 'NOUVEAU';
    case EN_COURS = 'EN_COURS';
    case FERME = 'FERME';
    case REFUSE = 'REFUSE';
}
