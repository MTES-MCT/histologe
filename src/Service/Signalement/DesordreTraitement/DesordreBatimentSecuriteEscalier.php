<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;

class DesordreBatimentSecuriteEscalier implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    public function findDesordresPrecisionsBy(array $payload, string $slug): array
    {
        $precisions = [];

        $slugUtilisable = 'desordres_batiment_securite_escalier_details_utilisable';
        if (isset($payload[$slugUtilisable])
            && 'oui' === $payload[$slugUtilisable]) {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => $slugUtilisable]
            );
            $precisions[] = $precision;
        }

        $slugDangereux = 'desordres_batiment_securite_escalier_details_dangereux';
        if (isset($payload[$slugDangereux])
            && 'oui' === $payload[$slugDangereux]) {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => $slugDangereux]
            );
            $precisions[] = $precision;
        }

        return $precisions;
    }
}
