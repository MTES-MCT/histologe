<?php

namespace App\Service\ListFilters;

use App\Entity\Territory;
use App\Service\Behaviour\SearchQueryTrait;

class SearchAutoAffectationRule
{
    use SearchQueryTrait;

    private ?Territory $territory = null;

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): void
    {
        $this->territory = $territory;
    }
}
