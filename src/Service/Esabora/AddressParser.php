<?php

namespace App\Service\Esabora;

use App\Service\Esabora\Enum\ExtensionAdresse;

class AddressParser
{
    public static function parse(string $address): array
    {
        $number = null;
        $suffix = null;

        preg_match('/^(\d+)\s*(\w+)?\s*/', $address, $matches);
        if (isset($matches[1])) {
            $number = $matches[1];
            if (isset($matches[2]) && \in_array(strtoupper($matches[2]), ExtensionAdresse::toArray())) {
                $suffix = strtoupper($matches[2]);
                $street = preg_replace('/^\d+\s*(?:\w+)?\s*/', '', $address);
            } else {
                preg_match('/^(\d+)\s+(.*)$/', $address, $matches);
                $street = $matches[2];
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
