<?php

namespace App\Entity\Enum;

enum PartnerType: string
{
    case ADIL = 'ADIL';
    case ARS = 'ARS';
    case ASSOCIATION = 'Association';
    case BAILLEUR_SOCIAL = 'Bailleur social';
    case CAF_MSA = 'CAF / MSA';
    case CCAS = 'CCAS';
    case COMMUNE_SCHS = 'Commune / SCHS';
    case CONCILIATEURS = 'Conciliateurs';
    case CONSEIL_DEPARTEMENTAL = 'Conseil départemental';
    case DDETS = 'DDETS';
    case DDT_M = 'DDT/M';
    case DISPOSITIF_RENOVATION_HABITAT = 'Dispositif rénovation habitat';
    case EPCI = 'EPCI';
    case OPERATEUR_VISITES_ET_TRAVAUX = 'Opérateur visites et travaux';
    case POLICE_GENDARMERIE = 'Police / Gendarmerie';
    case PREFECTURE = 'Préfecture';
    case TRIBUNAL = 'Tribunal';
    case AUTRE = 'Autre';
}
