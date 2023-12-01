<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;
use Doctrine\Common\Collections\ArrayCollection;

class DesordreLogementElectriciteManquePrises implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
        private readonly DesordreTraitementOuiNon $desordreTraitementOuiNon,
    ) {
    }

    public function process(array $payload, string $slug): ArrayCollection
    {
        $precisions = $this->desordreTraitementOuiNon->process(
            $payload,
            'desordres_logement_electricite_manque_prises_details_multiprises'
        );
        // TODO : à voir avec Mathilde, dans l'algo on ne prend pas en compte les pièces
        // dans lesquels on manque de prises desordres_logement_electricite_manque_prises_details_pieces

        return $precisions;
    }
}
