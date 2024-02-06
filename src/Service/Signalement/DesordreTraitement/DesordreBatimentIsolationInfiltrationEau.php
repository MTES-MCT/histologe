<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;

class DesordreBatimentIsolationInfiltrationEau implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    public function findDesordresPrecisionsBy(array $payload, string $slug): array
    {
        $precisions = [];

        if (\array_key_exists('type_logement_nature', $payload) && 'maison' === $payload['type_logement_nature']) {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => 'desordres_batiment_isolation_infiltration_eau_maison_individuelle']
            );
        } elseif (
            \array_key_exists('type_logement_sous_sol_sans_fenetre', $payload)
            && 'oui' === $payload['type_logement_sous_sol_sans_fenetre']
        ) {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => 'desordres_batiment_isolation_infiltration_eau_sous_sol']
            );
        } elseif (
            \array_key_exists('type_logement_rdc', $payload)
            && 'oui' === $payload['type_logement_rdc']) {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => 'desordres_batiment_isolation_infiltration_eau_rdc']
            );
        } else {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => 'desordres_batiment_isolation_infiltration_eau_au_sol_non']
            );
        }
        $precisions[] = $precision;

        return $precisions;
    }
}
