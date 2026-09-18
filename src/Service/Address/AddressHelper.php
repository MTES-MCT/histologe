<?php

namespace App\Service\Address;

class AddressHelper
{
    /**
     * @return array<string>
     */
    public static function getHouseNumberAndStreetFromAddress(string $address): array
    {
        $address = trim($address);

        $housenumber = null;
        $street = $address;

        // Essayer d'extraire le numéro de rue (premiers chiffres éventuellement suivis de bis, ter, quater)
        if (preg_match('/^(\d+(?:\s+(?:bis|ter|quater))?)\s+(.+)$/i', $address, $matches)) {
            $housenumber = trim($matches[1]);
            $street = trim($matches[2]);
        }

        return [$housenumber, $street];
    }
}
