<?php

namespace App\Utils;

use App\Utils\Enum\ExtensionAdresse;

class AddressParser
{
    public static function parse(string $address): array
    {
        $number = null;
        $suffix = null;
        $address = str_replace(',', '', $address);

        if (str_contains($address, '/')) {
            return [
                'number' => $number,
                'suffix' => $suffix,
                'street' => $address,
            ];
        }
        // Match number and optional suffix at the beginning of the address
        preg_match('/^(\d+)\s*(\w+)?\s*/', $address, $matches);
        if (isset($matches[1])) {
            $number = $matches[1];
            if (isset($matches[2]) && \in_array(strtoupper($matches[2]), ExtensionAdresse::toArray())) {
                $suffix = strtoupper($matches[2]);
                // Remove the number and suffix from the street
                $street = preg_replace('/^\d+\s*(?:\w+)?\s*/', '', $address);
            } else {
                preg_match('/^(\d+)\s+(.*)$/', $address, $matches);
                if (!empty($matches)) {
                    $street = $matches[2];
                } else {
                    // The number and the street are not separated by a space
                    // Remove number at the beginning of the address
                    $street = preg_replace('/^\d+\s*/', '', $address);
                }
            }
        } else {
            $street = $address;
        }

        return [
            'number' => $number,
            'suffix' => $suffix,
            'street' => ucfirst($street),
        ];
    }
}
