<?php

namespace App\Service\Statistics;

use App\Entity\Commune;
use App\Entity\Territory;
use Collator;

class ListCommunesStatisticProvider
{
    public function getData(?Territory $territory)
    {
        $data = [];
        if (null !== $territory) {
            $communes = $territory->getCommunes();
            /** @var Commune $commune */
            foreach ($communes as $commune) {
                $nomCommune = $commune->getNom();
                $data[$nomCommune] = $nomCommune;
            }
        }
        $collator = new Collator('fr_FR');
        $collator->asort($data);

        return $data;
    }
}
