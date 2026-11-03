<?php

namespace App\Service\ServiceSecours;

use App\Entity\Enum\ServiceSecoursCategory;

class ServiceSecoursCategoryResolver
{
    public const string PATTERN_MARINS_POMPIERS = '/(bmpm|marin)/i';
    public const string PATTERN_GENDARMERIE = '/(gendarmerie|gendarme)/i';
    public const string PATTERN_POLICE = '/(police|commissariat|crs)/i';
    public const string PATTERN_SDIS = '/(sdis|pompier|sapeur|secours|incendie|feu)/i';

    public function resolve(string $organizationalUnit): ServiceSecoursCategory
    {
        if (preg_match(self::PATTERN_MARINS_POMPIERS, $organizationalUnit)) {
            return ServiceSecoursCategory::MARINS_POMPIERS;
        }

        if (preg_match(self::PATTERN_GENDARMERIE, $organizationalUnit)) {
            return ServiceSecoursCategory::GENDARMERIE;
        }

        if (preg_match(self::PATTERN_POLICE, $organizationalUnit)) {
            return ServiceSecoursCategory::POLICE;
        }

        if (preg_match(self::PATTERN_SDIS, $organizationalUnit)) {
            return ServiceSecoursCategory::SDIS;
        }

        return ServiceSecoursCategory::AUTRE;
    }
}
