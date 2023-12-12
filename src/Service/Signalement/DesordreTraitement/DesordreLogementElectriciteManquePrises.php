<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;

class DesordreLogementElectriciteManquePrises implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
        private readonly DesordreTraitementOuiNon $desordreTraitementOuiNon,
    ) {
    }

    public function findDesordresPrecisionsBy(array $payload, string $slug): array
    {
        $precisions = $this->desordreTraitementOuiNon->findDesordresPrecisionsBy(
            $payload,
            'desordres_logement_electricite_manque_prises_details_multiprises'
        );

        return $precisions;
    }
}
