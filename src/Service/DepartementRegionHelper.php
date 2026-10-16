<?php

/**
 * Source: https://www.data.gouv.fr/datasets/departements-et-leurs-regions.
 */

namespace App\Service;

class DepartementRegionHelper
{
    private const REGION_AUVERGNE_RHONE_ALPES = 'Auvergne-Rhône-Alpes';
    private const REGION_BOURGOGNE_FRANCHE_COMTE = 'Bourgogne-Franche-Comté';
    private const REGION_BRETAGNE = 'Bretagne';
    private const REGION_CENTRE_VAL_DE_LOIRE = 'Centre-Val de Loire';
    private const REGION_CORSE = 'Corse';
    private const REGION_GRAND_EST = 'Grand Est';
    private const REGION_GUADELOUPE = 'Guadeloupe';
    private const REGION_GUYANE = 'Guyane';
    private const REGION_HAUTS_DE_FRANCE = 'Hauts-de-France';
    private const REGION_ILE_DE_FRANCE = 'Île-de-France';
    private const REGION_LA_REUNION = 'La Réunion';
    private const REGION_MARTINIQUE = 'Martinique';
    private const REGION_MAYOTTE = 'Mayotte';
    private const REGION_NORMANDIE = 'Normandie';
    private const REGION_NOUVELLE_AQUITAINE = 'Nouvelle-Aquitaine';
    private const REGION_OCCITANIE = 'Occitanie';
    private const REGION_PAYS_DE_LA_LOIRE = 'Pays de la Loire';
    private const REGION_PROVENCE_ALPES_COTE_D_AZUR = 'Provence-Alpes-Côte d\'Azur';

    /**
     * Correspondance entre le code département (Territory::zip) et le nom de sa région.
     */
    public const array DEPARTEMENT_REGION = [
        '01' => self::REGION_AUVERGNE_RHONE_ALPES, // Ain
        '02' => self::REGION_HAUTS_DE_FRANCE, // Aisne
        '03' => self::REGION_AUVERGNE_RHONE_ALPES, // Allier
        '04' => self::REGION_PROVENCE_ALPES_COTE_D_AZUR, // Alpes-de-Haute-Provence
        '05' => self::REGION_PROVENCE_ALPES_COTE_D_AZUR, // Hautes-Alpes
        '06' => self::REGION_PROVENCE_ALPES_COTE_D_AZUR, // Alpes-Maritimes
        '07' => self::REGION_AUVERGNE_RHONE_ALPES, // Ardèche
        '08' => self::REGION_GRAND_EST, // Ardennes
        '09' => self::REGION_OCCITANIE, // Ariège
        '10' => self::REGION_GRAND_EST, // Aube
        '11' => self::REGION_OCCITANIE, // Aude
        '12' => self::REGION_OCCITANIE, // Aveyron
        '13' => self::REGION_PROVENCE_ALPES_COTE_D_AZUR, // Bouches-du-Rhône
        '14' => self::REGION_NORMANDIE, // Calvados
        '15' => self::REGION_AUVERGNE_RHONE_ALPES, // Cantal
        '16' => self::REGION_NOUVELLE_AQUITAINE, // Charente
        '17' => self::REGION_NOUVELLE_AQUITAINE, // Charente-Maritime
        '18' => self::REGION_CENTRE_VAL_DE_LOIRE, // Cher
        '19' => self::REGION_NOUVELLE_AQUITAINE, // Corrèze
        '21' => self::REGION_BOURGOGNE_FRANCHE_COMTE, // Côte-d'Or
        '22' => self::REGION_BRETAGNE, // Côtes-d'Armor
        '23' => self::REGION_NOUVELLE_AQUITAINE, // Creuse
        '24' => self::REGION_NOUVELLE_AQUITAINE, // Dordogne
        '25' => self::REGION_BOURGOGNE_FRANCHE_COMTE, // Doubs
        '26' => self::REGION_AUVERGNE_RHONE_ALPES, // Drôme
        '27' => self::REGION_NORMANDIE, // Eure
        '28' => self::REGION_CENTRE_VAL_DE_LOIRE, // Eure-et-Loir
        '29' => self::REGION_BRETAGNE, // Finistère
        '2A' => self::REGION_CORSE, // Corse-du-Sud
        '2B' => self::REGION_CORSE, // Haute-Corse
        '30' => self::REGION_OCCITANIE, // Gard
        '31' => self::REGION_OCCITANIE, // Haute-Garonne
        '32' => self::REGION_OCCITANIE, // Gers
        '33' => self::REGION_NOUVELLE_AQUITAINE, // Gironde
        '34' => self::REGION_OCCITANIE, // Hérault
        '35' => self::REGION_BRETAGNE, // Ille-et-Vilaine
        '36' => self::REGION_CENTRE_VAL_DE_LOIRE, // Indre
        '37' => self::REGION_CENTRE_VAL_DE_LOIRE, // Indre-et-Loire
        '38' => self::REGION_AUVERGNE_RHONE_ALPES, // Isère
        '39' => self::REGION_BOURGOGNE_FRANCHE_COMTE, // Jura
        '40' => self::REGION_NOUVELLE_AQUITAINE, // Landes
        '41' => self::REGION_CENTRE_VAL_DE_LOIRE, // Loir-et-Cher
        '42' => self::REGION_AUVERGNE_RHONE_ALPES, // Loire
        '43' => self::REGION_AUVERGNE_RHONE_ALPES, // Haute-Loire
        '44' => self::REGION_PAYS_DE_LA_LOIRE, // Loire-Atlantique
        '45' => self::REGION_CENTRE_VAL_DE_LOIRE, // Loiret
        '46' => self::REGION_OCCITANIE, // Lot
        '47' => self::REGION_NOUVELLE_AQUITAINE, // Lot-et-Garonne
        '48' => self::REGION_OCCITANIE, // Lozère
        '49' => self::REGION_PAYS_DE_LA_LOIRE, // Maine-et-Loire
        '50' => self::REGION_NORMANDIE, // Manche
        '51' => self::REGION_GRAND_EST, // Marne
        '52' => self::REGION_GRAND_EST, // Haute-Marne
        '53' => self::REGION_PAYS_DE_LA_LOIRE, // Mayenne
        '54' => self::REGION_GRAND_EST, // Meurthe-et-Moselle
        '55' => self::REGION_GRAND_EST, // Meuse
        '56' => self::REGION_BRETAGNE, // Morbihan
        '57' => self::REGION_GRAND_EST, // Moselle
        '58' => self::REGION_BOURGOGNE_FRANCHE_COMTE, // Nièvre
        '59' => self::REGION_HAUTS_DE_FRANCE, // Nord
        '60' => self::REGION_HAUTS_DE_FRANCE, // Oise
        '61' => self::REGION_NORMANDIE, // Orne
        '62' => self::REGION_HAUTS_DE_FRANCE, // Pas-de-Calais
        '63' => self::REGION_AUVERGNE_RHONE_ALPES, // Puy-de-Dôme
        '64' => self::REGION_NOUVELLE_AQUITAINE, // Pyrénées-Atlantiques
        '65' => self::REGION_OCCITANIE, // Hautes-Pyrénées
        '66' => self::REGION_OCCITANIE, // Pyrénées-Orientales
        '67' => self::REGION_GRAND_EST, // Bas-Rhin
        '68' => self::REGION_GRAND_EST, // Haut-Rhin
        '69' => self::REGION_AUVERGNE_RHONE_ALPES, // Rhône
        '69A' => self::REGION_AUVERGNE_RHONE_ALPES, // Métropole de Lyon
        '70' => self::REGION_BOURGOGNE_FRANCHE_COMTE, // Haute-Saône
        '71' => self::REGION_BOURGOGNE_FRANCHE_COMTE, // Saône-et-Loire
        '72' => self::REGION_PAYS_DE_LA_LOIRE, // Sarthe
        '73' => self::REGION_AUVERGNE_RHONE_ALPES, // Savoie
        '74' => self::REGION_AUVERGNE_RHONE_ALPES, // Haute-Savoie
        '75' => self::REGION_ILE_DE_FRANCE, // Paris
        '76' => self::REGION_NORMANDIE, // Seine-Maritime
        '77' => self::REGION_ILE_DE_FRANCE, // Seine-et-Marne
        '78' => self::REGION_ILE_DE_FRANCE, // Yvelines
        '79' => self::REGION_NOUVELLE_AQUITAINE, // Deux-Sèvres
        '80' => self::REGION_HAUTS_DE_FRANCE, // Somme
        '81' => self::REGION_OCCITANIE, // Tarn
        '82' => self::REGION_OCCITANIE, // Tarn-et-Garonne
        '83' => self::REGION_PROVENCE_ALPES_COTE_D_AZUR, // Var
        '84' => self::REGION_PROVENCE_ALPES_COTE_D_AZUR, // Vaucluse
        '85' => self::REGION_PAYS_DE_LA_LOIRE, // Vendée
        '86' => self::REGION_NOUVELLE_AQUITAINE, // Vienne
        '87' => self::REGION_NOUVELLE_AQUITAINE, // Haute-Vienne
        '88' => self::REGION_GRAND_EST, // Vosges
        '89' => self::REGION_BOURGOGNE_FRANCHE_COMTE, // Yonne
        '90' => self::REGION_BOURGOGNE_FRANCHE_COMTE, // Territoire de Belfort
        '91' => self::REGION_ILE_DE_FRANCE, // Essonne
        '92' => self::REGION_ILE_DE_FRANCE, // Hauts-de-Seine
        '93' => self::REGION_ILE_DE_FRANCE, // Seine-Saint-Denis
        '94' => self::REGION_ILE_DE_FRANCE, // Val-de-Marne
        '95' => self::REGION_ILE_DE_FRANCE, // Val-d'Oise
        '971' => self::REGION_GUADELOUPE, // Guadeloupe
        '972' => self::REGION_MARTINIQUE, // Martinique
        '973' => self::REGION_GUYANE, // Guyane
        '974' => self::REGION_LA_REUNION, // La Réunion
        '976' => self::REGION_MAYOTTE, // Mayotte
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
