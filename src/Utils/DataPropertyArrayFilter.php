<?php

namespace App\Utils;

class DataPropertyArrayFilter
{
    public static function filterByPrefix(array $data, array $prefixes): array
    {
        $arrayFiltered = [];
        foreach ($data as $property => $value) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($property, $prefix)) {
                    $arrayFiltered[$property] = $value;
                    break;
                }
            }
        }

        return $arrayFiltered;
    }
}
