<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Entity\DesordrePrecision;
use App\Repository\DesordrePrecisionRepository;

class DesordreLogementAerationVentilationDefectueuse implements DesordreTraitementInterface
{
    public function __construct(
        /** @phpstan-ignore-next-line */
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
        private readonly DesordreTraitementPieces $desordreTraitementPieces,
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

        if (
            \array_key_exists('desordres_logement_aeration_ventilation_defectueuse_details_nettoyage', $payload)
        ) {
            if ('oui' === $payload['desordres_logement_aeration_ventilation_defectueuse_details_nettoyage']) {
                $precisions = $this->desordreTraitementPieces->findDesordresPrecisionsBy(
                    $payload,
                    'desordres_logement_aeration_ventilation_defectueuse_details',
                    '_nettoyage_oui'
                );
            } elseif ('non' === $payload['desordres_logement_aeration_ventilation_defectueuse_details_nettoyage']) {
                $precisions = $this->desordreTraitementPieces->findDesordresPrecisionsBy(
                    $payload,
                    'desordres_logement_aeration_ventilation_defectueuse_details',
                    '_nettoyage_non'
                );
            } else {
                $precisions = $this->desordreTraitementPieces->findDesordresPrecisionsBy(
                    $payload,
                    'desordres_logement_aeration_ventilation_defectueuse_details',
                    '_nettoyage_nsp'
                );
            }
        }

        return $precisions;
    }
}
