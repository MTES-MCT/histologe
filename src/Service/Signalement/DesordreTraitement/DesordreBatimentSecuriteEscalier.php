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

        $slugDangereux = 'desordres_batiment_securite_escalier_details_dangereux';
        if (isset($payload[$slugDangereux])) {
            if ('oui' === $payload[$slugDangereux]) {
                $precision = $this->desordrePrecisionRepository->findOneBy(
                    ['desordrePrecisionSlug' => $slugDangereux]
                );
                $precisions[] = $precision;
            } else {
                $slugUtilisable = 'desordres_batiment_securite_escalier_details_utilisable';
                if (isset($payload[$slugUtilisable])
                    && 'oui' === $payload[$slugUtilisable]) {
                    $precision = $this->desordrePrecisionRepository->findOneBy(
                        ['desordrePrecisionSlug' => $slugUtilisable]
                    );
                    $precisions[] = $precision;
                }
            }
        }

        return $precisions;
    }
}
