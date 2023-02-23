<?php

namespace App\Entity\Enum;

enum Qualification: string
{
    case Accompagnement_juridique = 'Accompagnement juridique';
    case Accompagnement_social = 'Accompagnement social';
    case Accompagnement_travaux = 'Accompagnement travaux';
    case Arretes = 'Arrêtés';
    case Conciliation = 'Conciliation';
    case Consignation_AL = 'Consignation AL';
    case DALO = 'DALO';
    case Diogene = 'Diogène';
    case FSL = 'FSL';
    case Hebergement_relogement = 'Hébergement / relogement';
    case Insalubrite = 'Insalubrité';
    case Mise_en_securite_Peril = 'Mise en sécurité / Péril';
    case Non_decence = 'Non décence';
    case Non_decence_energetique = 'Non décence énergétique';
    case Nuisibles = 'Nuisibles';
    case RSD = 'RSD';
    case Visites = 'Visites';
}
