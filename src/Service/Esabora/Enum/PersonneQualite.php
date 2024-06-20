<?php

namespace App\Service\Esabora\Enum;

enum PersonneQualite: string
{
    case MONSIEUR = '1';
    case MADAME = '2';
    case MAITRE = '4';
    case SOCIETE = '5';
    case MADAME_MONSIEUR = '6';
    case MONSIEUR_ET_MADAME = '7';
}
