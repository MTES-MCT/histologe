<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum AffectationNewStatus: string
{
    use EnumTrait;

    case NOUVEAU = 'NOUVEAU';
    case EN_COURS = 'EN_COURS';
    case FERME = 'FERME';
    case REFUSE = 'REFUSE';
}
