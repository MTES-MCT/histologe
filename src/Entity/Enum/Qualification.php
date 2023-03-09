<?php

namespace App\Entity\Enum;

enum Qualification: string
{
    case ACCOMPAGNEMENT_JURIDIQUE = 'Accompagnement juridique';
    case ACCOMPAGNEMENT_SOCIAL = 'Accompagnement social';
    case ACCOMPAGNEMENT_TRAVAUX = 'Accompagnement travaux';
    case ARRETES = 'Arrêtés';
    case CONCILIATION = 'Conciliation';
    case CONSIGNATION_AL = 'Consignation AL';
    case DALO = 'DALO';
    case DIOGENE = 'Diogène';
    case FSL = 'FSL';
    case HEBERGEMENT_RELOGEMENT = 'Hébergement / relogement';
    case INSALUBRITE = 'Insalubrité';
    case MISE_EN_SECURITE_PERIL = 'Mise en sécurité / Péril';
    case NON_DECENCE = 'Non décence';
    case NON_DECENCE_ENERGETIQUE = 'Non décence énergétique';
    case NUISIBLES = 'Nuisibles';
    case RSD = 'RSD';
    case VISITES = 'Visites';
}
