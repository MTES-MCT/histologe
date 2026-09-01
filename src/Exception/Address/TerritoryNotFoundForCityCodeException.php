<?php

namespace App\Exception\Address;

class TerritoryNotFoundForCityCodeException extends \Exception
{
    public function __construct(string $cityCode)
    {
        parent::__construct(\sprintf('Aucun territoire trouvé pour le code INSEE %s', $cityCode));
    }
}
