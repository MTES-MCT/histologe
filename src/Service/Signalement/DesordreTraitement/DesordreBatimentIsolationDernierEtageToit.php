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

        if (
            \array_key_exists('type_logement_nature', $payload)
           && 'maison' === $payload['type_logement_nature']) {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => 'desordres_batiment_isolation_dernier_etage_toit_maison_individuelle']
            );
        } elseif (
            \array_key_exists('type_logement_sous_comble_sans_fenetre', $payload)
            && 'oui' === $payload['type_logement_sous_comble_sans_fenetre']
        ) {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => 'desordres_batiment_isolation_dernier_etage_toit_sous_combles']
            );
        } elseif (
            \array_key_exists('type_logement_dernier_etage', $payload)
            && 'oui' === $payload['type_logement_dernier_etage']) {
            $precision = $this->desordrePrecisionRepository->findOneBy(
                ['desordrePrecisionSlug' => 'desordres_batiment_isolation_dernier_etage_toit_dernier_etage']
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
