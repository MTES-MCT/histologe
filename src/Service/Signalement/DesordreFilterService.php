<?php

namespace App\Service\Signalement;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class DesordreFilterService
{
    public function __construct(private readonly PropertyAccessorInterface $propertyAccessor)
    {
    }

    public function filterDesordreData(array $jsonData, string $categorySlug): array
    {
        $filteredData = [];

        foreach ($jsonData as $key => $value) {
            if (0 === strpos($key, $categorySlug)) {
                $filteredData[$key] = $value;
            }
        }

        return $filteredData;
    }
}
