<?php

namespace App\Service\Esabora\Enum;

enum EsaboraStatus: string
{
    case ESABORA_WAIT = 'A traiter';
    case ESABORA_ACCEPTED = 'Importé';
    case ESABORA_IN_PROGRESS = 'en cours';
    case ESABORA_CLOSED = 'terminé';
    case ESABORA_REFUSED = 'Non importé';
    case ESABORA_REJECTED = 'Rejeté';
}
