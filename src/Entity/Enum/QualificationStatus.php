<?php

namespace App\Entity\Enum;

enum QualificationStatus: string
{
    case NDE_AVEREE = 'Non décence énergétique avérée';
    case NDE_OK = 'Décence énergétique OK';
    case NDE_CHECK = 'Non décence énergétique à vérifier';
}
