<?php

namespace App\Exception\Address;

class CityNotFoundException extends \Exception
{
    public function __construct(string $city)
    {
        parent::__construct(\sprintf('Commune %s introuvable', $city));
    }
}
