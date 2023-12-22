<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;

class DesordreBatimentSecuriteMursFissures implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
        private readonly DesordreTraitementOuiNon $desordreTraitementOuiNon,
    ) {
    }

    public function findDesordresPrecisionsBy(array $payload, string $slug): array
    {
        $precisions = [];

        $precisions = $this->desordreTraitementOuiNon->findDesordresPrecisionsBy(
            $payload,
            'desordres_batiment_securite_murs_fissures_details_mur_porteur'
        );

        return $precisions;
    }
}
