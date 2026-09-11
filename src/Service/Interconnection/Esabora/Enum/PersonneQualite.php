<?php

namespace App\Service\Interconnection\Esabora\Enum;

enum PersonneQualite: string
{
    case MONSIEUR = '1';
    case MADAME = '2';
    case SOCIETE = '5';
    case MADAME_MONSIEUR = '6';
}
