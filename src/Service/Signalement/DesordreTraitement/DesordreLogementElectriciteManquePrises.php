<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Entity\DesordrePrecision;
use App\Repository\DesordrePrecisionRepository;

class DesordreLogementElectriciteManquePrises implements DesordreTraitementInterface
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
        $precisions = $this->desordreTraitementOuiNon->findDesordresPrecisionsBy(
            $payload,
            'desordres_logement_electricite_manque_prises_details_multiprises'
        );

        return $precisions;
    }
}
