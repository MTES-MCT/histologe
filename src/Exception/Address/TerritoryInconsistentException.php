<?php

namespace App\Exception\Address;

use App\Entity\Territory;

class TerritoryInconsistentException extends \Exception
{
    public function __construct(Territory $actualTerritory, Territory $expectedTerritory)
    {
        parent::__construct(\sprintf(
            'Le territoire calculé (%s) pour l\'adresse ne correspond pas au territoire attendu (%s).',
            $actualTerritory->getName(),
            $expectedTerritory->getName()
        ));
    }
}
