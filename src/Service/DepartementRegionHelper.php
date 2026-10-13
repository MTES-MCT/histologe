<?php

/**
 * Source: https://www.data.gouv.fr/datasets/departements-et-leurs-regions.
 */

namespace App\Service;

class DepartementRegionHelper
{
    /**
     * Correspondance entre le code département (Territory::zip) et le nom de sa région.
     */
    public const array DEPARTEMENT_REGION = [
        '01' => 'Auvergne-Rhône-Alpes', // Ain
        '02' => 'Hauts-de-France', // Aisne
        '03' => 'Auvergne-Rhône-Alpes', // Allier
        '04' => 'Provence-Alpes-Côte d\'Azur', // Alpes-de-Haute-Provence
        '05' => 'Provence-Alpes-Côte d\'Azur', // Hautes-Alpes
        '06' => 'Provence-Alpes-Côte d\'Azur', // Alpes-Maritimes
        '07' => 'Auvergne-Rhône-Alpes', // Ardèche
        '08' => 'Grand Est', // Ardennes
        '09' => 'Occitanie', // Ariège
        '10' => 'Grand Est', // Aube
        '11' => 'Occitanie', // Aude
        '12' => 'Occitanie', // Aveyron
        '13' => 'Provence-Alpes-Côte d\'Azur', // Bouches-du-Rhône
        '14' => 'Normandie', // Calvados
        '15' => 'Auvergne-Rhône-Alpes', // Cantal
        '16' => 'Nouvelle-Aquitaine', // Charente
        '17' => 'Nouvelle-Aquitaine', // Charente-Maritime
        '18' => 'Centre-Val de Loire', // Cher
        '19' => 'Nouvelle-Aquitaine', // Corrèze
        '21' => 'Bourgogne-Franche-Comté', // Côte-d'Or
        '22' => 'Bretagne', // Côtes-d'Armor
        '23' => 'Nouvelle-Aquitaine', // Creuse
        '24' => 'Nouvelle-Aquitaine', // Dordogne
        '25' => 'Bourgogne-Franche-Comté', // Doubs
        '26' => 'Auvergne-Rhône-Alpes', // Drôme
        '27' => 'Normandie', // Eure
        '28' => 'Centre-Val de Loire', // Eure-et-Loir
        '29' => 'Bretagne', // Finistère
        '2A' => 'Corse', // Corse-du-Sud
        '2B' => 'Corse', // Haute-Corse
        '30' => 'Occitanie', // Gard
        '31' => 'Occitanie', // Haute-Garonne
        '32' => 'Occitanie', // Gers
        '33' => 'Nouvelle-Aquitaine', // Gironde
        '34' => 'Occitanie', // Hérault
        '35' => 'Bretagne', // Ille-et-Vilaine
        '36' => 'Centre-Val de Loire', // Indre
        '37' => 'Centre-Val de Loire', // Indre-et-Loire
        '38' => 'Auvergne-Rhône-Alpes', // Isère
        '39' => 'Bourgogne-Franche-Comté', // Jura
        '40' => 'Nouvelle-Aquitaine', // Landes
        '41' => 'Centre-Val de Loire', // Loir-et-Cher
        '42' => 'Auvergne-Rhône-Alpes', // Loire
        '43' => 'Auvergne-Rhône-Alpes', // Haute-Loire
        '44' => 'Pays de la Loire', // Loire-Atlantique
        '45' => 'Centre-Val de Loire', // Loiret
        '46' => 'Occitanie', // Lot
        '47' => 'Nouvelle-Aquitaine', // Lot-et-Garonne
        '48' => 'Occitanie', // Lozère
        '49' => 'Pays de la Loire', // Maine-et-Loire
        '50' => 'Normandie', // Manche
        '51' => 'Grand Est', // Marne
        '52' => 'Grand Est', // Haute-Marne
        '53' => 'Pays de la Loire', // Mayenne
        '54' => 'Grand Est', // Meurthe-et-Moselle
        '55' => 'Grand Est', // Meuse
        '56' => 'Bretagne', // Morbihan
        '57' => 'Grand Est', // Moselle
        '58' => 'Bourgogne-Franche-Comté', // Nièvre
        '59' => 'Hauts-de-France', // Nord
        '60' => 'Hauts-de-France', // Oise
        '61' => 'Normandie', // Orne
        '62' => 'Hauts-de-France', // Pas-de-Calais
        '63' => 'Auvergne-Rhône-Alpes', // Puy-de-Dôme
        '64' => 'Nouvelle-Aquitaine', // Pyrénées-Atlantiques
        '65' => 'Occitanie', // Hautes-Pyrénées
        '66' => 'Occitanie', // Pyrénées-Orientales
        '67' => 'Grand Est', // Bas-Rhin
        '68' => 'Grand Est', // Haut-Rhin
        '69' => 'Auvergne-Rhône-Alpes', // Rhône
        '69A' => 'Auvergne-Rhône-Alpes', // Métropole de Lyon
        '70' => 'Bourgogne-Franche-Comté', // Haute-Saône
        '71' => 'Bourgogne-Franche-Comté', // Saône-et-Loire
        '72' => 'Pays de la Loire', // Sarthe
        '73' => 'Auvergne-Rhône-Alpes', // Savoie
        '74' => 'Auvergne-Rhône-Alpes', // Haute-Savoie
        '75' => 'Île-de-France', // Paris
        '76' => 'Normandie', // Seine-Maritime
        '77' => 'Île-de-France', // Seine-et-Marne
        '78' => 'Île-de-France', // Yvelines
        '79' => 'Nouvelle-Aquitaine', // Deux-Sèvres
        '80' => 'Hauts-de-France', // Somme
        '81' => 'Occitanie', // Tarn
        '82' => 'Occitanie', // Tarn-et-Garonne
        '83' => 'Provence-Alpes-Côte d\'Azur', // Var
        '84' => 'Provence-Alpes-Côte d\'Azur', // Vaucluse
        '85' => 'Pays de la Loire', // Vendée
        '86' => 'Nouvelle-Aquitaine', // Vienne
        '87' => 'Nouvelle-Aquitaine', // Haute-Vienne
        '88' => 'Grand Est', // Vosges
        '89' => 'Bourgogne-Franche-Comté', // Yonne
        '90' => 'Bourgogne-Franche-Comté', // Territoire de Belfort
        '91' => 'Île-de-France', // Essonne
        '92' => 'Île-de-France', // Hauts-de-Seine
        '93' => 'Île-de-France', // Seine-Saint-Denis
        '94' => 'Île-de-France', // Val-de-Marne
        '95' => 'Île-de-France', // Val-d'Oise
        '971' => 'Guadeloupe', // Guadeloupe
        '972' => 'Martinique', // Martinique
        '973' => 'Guyane', // Guyane
        '974' => 'La Réunion', // La Réunion
        '976' => 'Mayotte', // Mayotte
    ];

    public static function getRegionByDepartement(string $zip): ?string
    {
        return self::DEPARTEMENT_REGION[$zip] ?? null;
    }

    /**
     * @return array<string>
     */
    public static function getRegions(): array
    {
        return array_values(array_unique(self::DEPARTEMENT_REGION));
    }
}
