<?php

namespace App\Entity\Enum\Api;

enum PersonneType: string
{
    case DECLARANT = 'DECLARANT';
    case OCCUPANT = 'OCCUPANT';
    case PROPRIETAIRE = 'PROPRIETAIRE';
    case AGENCE = 'AGENCE';
}
