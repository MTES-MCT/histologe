<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Entity\DesordrePrecision;
use App\Repository\DesordrePrecisionRepository;

class DesordreBatimentSecuriteMursFissures implements DesordreTraitementInterface
{
    public function __construct(
        /** @phpstan-ignore-next-line */
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
        private readonly DesordreTraitementOuiNon $desordreTraitementOuiNon,
    ) {
    }

    /**
     * @param array<string, string> $payload
     *
     * @return array<DesordrePrecision|null>
     */
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
