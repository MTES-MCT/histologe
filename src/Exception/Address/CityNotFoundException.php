<?php

namespace App\Exception\Address;

class CityNotFoundException extends \Exception
{
    public function __construct(string $city, string $postalCode)
    {
        parent::__construct(\sprintf('Commune %s introuvable pour le code postal %s', $city, $postalCode));
    }
}
