<?php

namespace App\Entity\Enum;

/** should be replace by enum with php 8.1 */
class MotifCloture
{
    public const LABEL = [
        'RESOLU' => 'Problème résolu',
        'NON_DECENCE' => 'Non décence',
        'INFRACTION RSD' => 'Infraction RSD',
        'INSALUBRITE' => 'Insalubrité',
        'LOGEMENT DECENT' => 'Logement décent',
        'LOCATAIRE PARTI' => 'Locataire parti',
        'LOGEMENT VENDU' => 'Logement vendu',
        'AUTRE' => 'Autre',
    ];
}
