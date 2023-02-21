<?php

namespace App\Entity\Enum;

enum PartnerType: string
{
    case ADIL = 'ADIL';
    case ARS = 'ARS';
    case Association = 'Association';
    case Bailleur_social = 'Bailleur social';
    case CAF_MSA = 'CAF / MSA';
    case CCAS = 'CCAS';
    case Commune_SCHS = 'Commune / SCHS';
    case Conciliateurs = 'Conciliateurs';
    case Conseil_departemental = 'Conseil départemental';
    case DDETS = 'DDETS';
    case DDT_M = 'DDT/M';
    case Dispositif_renovation_habitat = 'Dispositif rénovation habitat';
    case EPCI = 'EPCI';
    case Operateur_visites_et_travaux = 'Opérateur visites et travaux';
    case Police_Gendarmerie = 'Police / Gendarmerie';
    case Prefecture = 'Préfecture';
    case Tribunal = 'Tribunal';
    case Autre = 'Autre';
}
