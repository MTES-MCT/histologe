<?php

namespace App\Service\ServiceSecours;

use App\Entity\Enum\ServiceSecoursCategory;

class ServiceSecoursCategoryResolver
{
    public function resolve(string $organizationalUnit): ServiceSecoursCategory
    {
        if (preg_match('/(bmpm|marin)/i', $organizationalUnit)) {
            return ServiceSecoursCategory::MARINS_POMPIERS;
        }

        if (preg_match('/(gendarmerie|gendarme)/i', $organizationalUnit)) {
            return ServiceSecoursCategory::GENDARMERIE;
        }

        if (preg_match('/(police|commissariat|crs)/i', $organizationalUnit)) {
            return ServiceSecoursCategory::POLICE;
        }

        if (preg_match('/(sdis|pompier|sapeur|secours|incendie|feu)/i', $organizationalUnit)) {
            return ServiceSecoursCategory::SDIS;
        }

        return ServiceSecoursCategory::AUTRE;
    }
}
