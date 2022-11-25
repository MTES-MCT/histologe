<?php

namespace App\Service\Statistics;

use App\Entity\Territory;
use Collator;

class ListCommunesStatisticProvider
{
    private const COMMUNE_MARSEILLE = 'Marseille';
    private const COMMUNE_LYON = 'Lyon';
    private const COMMUNE_PARIS = 'Paris';

    public function __construct()
    {
    }

    public function getData(?Territory $territory)
    {
        $buffer = [];
        if (null !== $territory) {
            $communes = $territory->getCommunes();
            /** @var Commune $commune */
            foreach ($communes as $commune) {
                // Controls over 3 Communes with Arrondissements that we don't want
                $nomCommune = $commune->getNom();
                if (preg_match('/('.self::COMMUNE_MARSEILLE.')(.)*(Arrondissement)/', $nomCommune)) {
                    $nomCommune = self::COMMUNE_MARSEILLE;
                }
                if (preg_match('/('.self::COMMUNE_LYON.')(.)*(Arrondissement)/', $nomCommune)) {
                    $nomCommune = self::COMMUNE_LYON;
                }
                if (preg_match('/('.self::COMMUNE_PARIS.')(.)*(Arrondissement)/', $nomCommune)) {
                    $nomCommune = self::COMMUNE_PARIS;
                }
                $buffer[$nomCommune] = $nomCommune;
            }
        }
        $collator = new Collator('fr_FR');
        $collator->asort($buffer);

        return $buffer;
    }
}
