<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;

class DesordreLogementAerationVentilationDefectueuse implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
        private readonly DesordreTraitementPieces $desordreTraitementPieces,
    ) {
    }

    public function findDesordresPrecisionsBy(array $payload, string $slug): array
    {
        $precisions = [];

        if (isset($payload['desordres_logement_aeration_ventilation_defectueuse_details_nettoyage'])) {
            if ('oui' === $payload['desordres_logement_aeration_ventilation_defectueuse_details_nettoyage']) {
                $precisions = $this->desordreTraitementPieces->findDesordresPrecisionsBy(
                    $payload,
                    'desordres_logement_aeration_ventilation_defectueuse_details',
                    '_nettoyage_oui'
                );
            } else {
                $precisions = $this->desordreTraitementPieces->findDesordresPrecisionsBy(
                    $payload,
                    'desordres_logement_aeration_ventilation_defectueuse_details',
                    '_nettoyage_non'
                );
            }
        }

        return $precisions;
    }
}
