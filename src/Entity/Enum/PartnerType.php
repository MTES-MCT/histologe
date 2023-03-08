<?php

namespace App\Entity\Enum;

enum PartnerType: string
{
    case ADIL = 'ADIL';
    case ARS = 'ARS';
    case ASSOCIATION = 'ASSOCIATION';
    case BAILLEUR_SOCIAL = 'BAILLEUR_SOCIAL';
    case CAF_MSA = 'CAF_MSA';
    case CCAS = 'CCAS';
    case COMMUNE_SCHS = 'COMMUNE_SCHS';
    case CONCILIATEURS = 'CONCILIATEURS';
    case CONSEIL_DEPARTEMENTAL = 'CONSEIL_DEPARTEMENTAL';
    case DDETS = 'DDETS';
    case DDT_M = 'DDT_M';
    case DISPOSITIF_RENOVATION_HABITAT = 'DISPOSITIF_RENOVATION_HABITAT';
    case EPCI = 'EPCI';
    case OPERATEUR_VISITES_ET_TRAVAUX = 'OPERATEUR_VISITES_ET_TRAVAUX';
    case POLICE_GENDARMERIE = 'POLICE_GENDARMERIE';
    case PREFECTURE = 'PREFECTURE';
    case TRIBUNAL = 'TRIBUNAL';
    case AUTRE = 'AUTRE';

    public function label(): string
    {
        return self::getLabelList()[$this->name];
    }

    public static function getLabelList(): array
    {
        return [
            'ADIL' => 'ADIL',
            'ARS' => 'ARS',
            'ASSOCIATION' => 'Association',
            'BAILLEUR_SOCIAL' => 'Bailleur social',
            'CAF_MSA' => 'CAF / MSA',
            'CCAS' => 'CCAS',
            'COMMUNE_SCHS' => 'Commune / SCHS',
            'CONCILIATEURS' => 'Conciliateurs',
            'CONSEIL_DEPARTEMENTAL' => 'Conseil départemental',
            'DDETS' => 'DDETS',
            'DDT_M' => 'DDT/M',
            'DISPOSITIF_RENOVATION_HABITAT' => 'Dispositif rénovation habitat',
            'EPCI' => 'EPCI',
            'OPERATEUR_VISITES_ET_TRAVAUX' => 'Opérateur visites et travaux',
            'POLICE_GENDARMERIE' => 'Police / Gendarmerie',
            'PREFECTURE' => 'Préfecture',
            'TRIBUNAL' => 'Tribunal',
            'AUTRE' => 'Autre',
        ];
    }
}
