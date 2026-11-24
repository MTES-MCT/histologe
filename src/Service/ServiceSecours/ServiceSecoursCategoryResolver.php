<?php

namespace App\Service\ServiceSecours;

use App\Entity\Enum\ServiceSecoursCategory;
use Sentry\Severity;
use Sentry\State\Scope;
use function Sentry\captureMessage;
use function Sentry\withScope;

class ServiceSecoursCategoryResolver
{
    public const string PATTERN_MARINS_POMPIERS = '/(bmpm|marin)/i';
    public const string PATTERN_GENDARMERIE = '/(gendarmerie|gendarme)/i';
    public const string PATTERN_POLICE = '/(police|commissariat|crs)/i';
    public const string PATTERN_SDIS = '/(sdis|pompier|sapeur|secours|incendie|feu)/i';

    public function resolve(?string $organizationValue = null, string $key = 'organization_label'): ServiceSecoursCategory
    {
        if (preg_match(self::PATTERN_MARINS_POMPIERS, $organizationValue)) {
            return ServiceSecoursCategory::MARINS_POMPIERS;
        }

        if (preg_match(self::PATTERN_GENDARMERIE, $organizationValue)) {
            return ServiceSecoursCategory::GENDARMERIE;
        }

        if (preg_match(self::PATTERN_POLICE, $organizationValue)) {
            return ServiceSecoursCategory::POLICE;
        }

        if (preg_match(self::PATTERN_SDIS, $organizationValue)) {
            return ServiceSecoursCategory::SDIS;
        }
        withScope(static function (Scope $scope) use ($organizationValue, $key): void {
            $scope->setLevel(Severity::warning());
            $scope->setExtra($key, $organizationValue);
            captureMessage(
                sprintf('[Service-secours] %s non résolu : %s', $key, $organizationValue),
                Severity::warning()
            );
        });

        return ServiceSecoursCategory::AUTRE;
    }
}
