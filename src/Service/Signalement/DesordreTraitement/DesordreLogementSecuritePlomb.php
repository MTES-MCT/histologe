<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;

class DesordreLogementSecuritePlomb implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
        private readonly DesordreTraitementPieces $desordreTraitementPieces,
    ) {
    }

    public function findDesordresPrecisionsBy(array $payload, string $slug): array
    {
        $precisions = [];

        if (\array_key_exists('desordres_logement_securite_plomb_details_diagnostique', $payload)) {
            if ('oui' === $payload['desordres_logement_securite_plomb_details_diagnostique']) {
                $precisions = $this->desordreTraitementPieces->findDesordresPrecisionsBy(
                    $payload,
                    'desordres_logement_securite_plomb',
                    '_diagnostique_oui'
                );
            } else {
                $precisions = $this->desordreTraitementPieces->findDesordresPrecisionsBy(
                    $payload,
                    'desordres_logement_securite_plomb',
                    '_diagnostique_non'
                );
            }
        }

        return $precisions;
    }
}
