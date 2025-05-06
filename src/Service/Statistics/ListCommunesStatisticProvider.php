<?php

namespace App\Service\Statistics;

use App\Entity\Commune;
use App\Entity\Territory;

class ListCommunesStatisticProvider
{
    /** @return array<string, string> */
    public function getData(?Territory $territory): array
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
        $collator = new \Collator('fr_FR');
        $collator->asort($data);

        return $data;
    }
}
