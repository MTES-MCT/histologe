<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Repository\DesordrePrecisionRepository;

class DesordreBatimentIsolationDernierEtageToit implements DesordreTraitementInterface
{
    public function __construct(
        private readonly DesordrePrecisionRepository $desordrePrecisionRepository,
    ) {
    }

    public function findDesordresPrecisionsBy(array $payload, string $slug): array
    {
        $precisions = [];

        if (isset($payload['type_logement_nature']) && 'maison' === $payload['type_logement_nature']
            || (
                \array_key_exists('type_logement_sous_comble_sans_fenetre', $payload)
                && 'oui' === $payload['type_logement_sous_comble_sans_fenetre']
            )
            || (
                \array_key_exists('type_logement_dernier_etage', $payload)
                && 'oui' === $payload['type_logement_dernier_etage']
            )) {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => 'desordres_batiment_isolation_dernier_etage_toit_sous_toit_oui']
            );
        } else {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => 'desordres_batiment_isolation_dernier_etage_toit_sous_toit_non']
            );
        }
        $precisions[] = $precision;

        return $precisions;
    }
}
