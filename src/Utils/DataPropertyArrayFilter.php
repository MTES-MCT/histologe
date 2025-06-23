<?php

namespace App\Utils;

class DataPropertyArrayFilter
{
    /**
     * @param array<mixed> $data
     * @param array<mixed> $prefixes
     * @return array<mixed>
     */
    public static function filterByPrefix(array $data, array $prefixes): array
    {
        $arrayFiltered = [];
        foreach ($data as $property => $value) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($property, $prefix) && !str_ends_with($property, '_upload')) {
                    $arrayFiltered[$property] = $value;
                    break;
                }
            }
        }

        return $arrayFiltered;
    }
}
