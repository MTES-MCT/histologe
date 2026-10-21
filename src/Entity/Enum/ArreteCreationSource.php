<?php

namespace App\Entity\Enum;

enum ArreteCreationSource: string
{
    case IMPORT = 'IMPORT';
    case ESABORA = 'ESABORA';
    case API = 'API';
}
